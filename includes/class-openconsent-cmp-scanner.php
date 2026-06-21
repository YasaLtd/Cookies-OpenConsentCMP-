<?php
/**
 * Local crawler scanner.
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scanner controller.
 */
final class OpenConsent_CMP_Scanner {
	/**
	 * Scan the site for static cookie-related findings.
	 *
	 * @param int $page_limit Maximum internal pages to scan.
	 * @return array
	 */
	public function scan_site( $page_limit = 5 ) {
		$page_limit = max( 1, min( 25, absint( $page_limit ) ) );
		$queue      = array( home_url( '/' ) );
		$visited    = array();
		$pages      = array();
		$cookies    = array();
		$resources  = array();
		$suggestions = array();

		while ( ! empty( $queue ) && count( $visited ) < $page_limit ) {
			$url = array_shift( $queue );
			$key = untrailingslashit( $url );
			if ( isset( $visited[ $key ] ) ) {
				continue;
			}

			$visited[ $key ] = true;
			$page = $this->scan_url( $url );
			$pages[] = $page;

			foreach ( $page['cookies'] as $cookie ) {
				$cookies[ $cookie['name'] ] = $cookie;
			}

			foreach ( $page['resources'] as $resource ) {
				$resources[ $resource['host'] . '|' . $resource['type'] ] = $resource;
				if ( empty( $resource['matched'] ) ) {
					$suggestions[ $resource['host'] ] = array(
						'pattern'     => $resource['host'],
						'category'    => $this->suggest_category( $resource['host'] ),
						'name'        => $resource['host'],
						'provider'    => '',
						'purpose'     => '',
						'privacy_url' => '',
					);
				}
			}

			foreach ( $page['internal_links'] as $link ) {
				if ( count( $queue ) + count( $visited ) >= $page_limit ) {
					break;
				}
				if ( ! isset( $visited[ untrailingslashit( $link ) ] ) ) {
					$queue[] = $link;
				}
			}
		}

		return array(
			'version'      => 2,
			'generated_at' => current_time( 'mysql' ),
			'page_limit'   => $page_limit,
			'summary'      => array(
				'pages_scanned'      => count( $pages ),
				'cookies_found'      => count( $cookies ),
				'external_resources' => count( $resources ),
				'suggestions'        => count( $suggestions ),
			),
			'pages'        => $pages,
			'cookies'      => array_values( $cookies ),
			'resources'    => array_values( $resources ),
			'suggestions'  => array_values( $suggestions ),
		);
	}

	/**
	 * Scan the homepage for static cookie-related findings.
	 *
	 * @return array
	 */
	public function scan_homepage() {
		return $this->legacy_findings( $this->scan_site( 1 ) );
	}

	/**
	 * Scan one URL.
	 *
	 * @param string $url URL to scan.
	 * @return array
	 */
	private function scan_url( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'user-agent'  => 'OpenConsent CMP Scanner/' . OPENCONSENT_CMP_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'url'            => esc_url_raw( $url ),
				'status'         => 'error',
				'message'        => sanitize_text_field( $response->get_error_message() ),
				'cookies'        => array(),
				'resources'      => array(),
				'internal_links' => array(),
			);
		}

		$headers  = wp_remote_retrieve_headers( $response );
		$body     = wp_remote_retrieve_body( $response );
		$cookies  = array();

		if ( isset( $headers['set-cookie'] ) ) {
			$headers_cookies = is_array( $headers['set-cookie'] ) ? $headers['set-cookie'] : array( $headers['set-cookie'] );
			foreach ( $headers_cookies as $cookie ) {
				$cookies[] = $this->parse_cookie( $cookie, $url );
			}
		}

		return array(
			'url'            => esc_url_raw( $url ),
			'status'         => absint( wp_remote_retrieve_response_code( $response ) ),
			'cookies'        => array_values( array_filter( $cookies ) ),
			'resources'      => $this->extract_resources( $body ),
			'internal_links' => $this->extract_internal_links( $body, $url ),
		);
	}

	/**
	 * Extract cookie name.
	 *
	 * @param string $cookie Cookie header.
	 * @return string
	 */
	private function cookie_name( $cookie ) {
		$parts = explode( '=', (string) $cookie, 2 );

		return sanitize_text_field( $parts[0] );
	}

	/**
	 * Parse cookie header.
	 *
	 * @param string $cookie Cookie header.
	 * @param string $url    Page URL.
	 * @return array
	 */
	private function parse_cookie( $cookie, $url ) {
		$parts = array_map( 'trim', explode( ';', (string) $cookie ) );
		$name  = $this->cookie_name( $parts[0] ?? '' );
		if ( '' === $name ) {
			return array();
		}

		$expires = '';
		foreach ( $parts as $part ) {
			if ( 0 === stripos( $part, 'expires=' ) || 0 === stripos( $part, 'max-age=' ) ) {
				$expires = sanitize_text_field( $part );
			}
		}

		return array(
			'name'     => $name,
			'expires'  => $expires,
			'page_url' => esc_url_raw( $url ),
		);
	}

	/**
	 * Extract external hosts from static markup.
	 *
	 * @param string $html HTML body.
	 * @return array
	 */
	private function extract_resources( $html ) {
		$resources = array();
		$home  = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! preg_match_all( '/<(script|iframe|img|link)\b[^>]+(?:src|href)=["\']([^"\']+)["\']/i', (string) $html, $matches, PREG_SET_ORDER ) ) {
			return $resources;
		}

		foreach ( $matches as $match ) {
			$type = strtolower( $match[1] );
			$url  = html_entity_decode( $match[2] );
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( $host && $home && strtolower( $host ) !== strtolower( $home ) ) {
				$resources[] = array(
					'type'     => sanitize_key( $type ),
					'host'     => sanitize_text_field( $host ),
					'url'      => esc_url_raw( $url ),
					'matched'  => $this->matches_configured_service( $url ),
					'category' => $this->suggest_category( $host ),
				);
			}
		}

		return array_values( array_unique( $resources, SORT_REGULAR ) );
	}

	/**
	 * Extract internal crawl links.
	 *
	 * @param string $html     HTML body.
	 * @param string $base_url Base URL.
	 * @return array
	 */
	private function extract_internal_links( $html, $base_url ) {
		$links = array();
		$home  = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! preg_match_all( '/<a\b[^>]+href=["\']([^"\']+)["\']/i', (string) $html, $matches ) ) {
			return $links;
		}

		foreach ( $matches[1] as $href ) {
			$url = $this->absolute_url( html_entity_decode( $href ), $base_url );
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( $url && $host && $home && strtolower( $host ) === strtolower( $home ) && ! preg_match( '/\.(zip|pdf|jpg|jpeg|png|gif|webp|svg)$/i', wp_parse_url( $url, PHP_URL_PATH ) ?: '' ) ) {
				$links[] = esc_url_raw( strtok( $url, '#' ) );
			}
		}

		return array_values( array_unique( $links ) );
	}

	/**
	 * Convert relative URL to absolute.
	 *
	 * @param string $href     Link href.
	 * @param string $base_url Base URL.
	 * @return string
	 */
	private function absolute_url( $href, $base_url ) {
		if ( preg_match( '/^https?:\/\//i', $href ) ) {
			return $href;
		}
		if ( 0 === strpos( $href, '//' ) ) {
			return is_ssl() ? 'https:' . $href : 'http:' . $href;
		}
		if ( 0 === strpos( $href, '/' ) ) {
			return home_url( $href );
		}

		return trailingslashit( dirname( $base_url ) ) . ltrim( $href, '/' );
	}

	/**
	 * Check whether a URL matches configured service registry.
	 *
	 * @param string $url Resource URL.
	 * @return bool
	 */
	private function matches_configured_service( $url ) {
		$options = get_option( OpenConsent_CMP::OPTION, array() );
		$rows = preg_split( '/\r\n|\r|\n/', (string) ( is_array( $options ) ? ( $options['services'] ?? '' ) : '' ) );
		foreach ( $rows as $row ) {
			$parts = array_map( 'trim', explode( '|', $row ) );
			if ( ! empty( $parts[0] ) && false !== stripos( $url, $parts[0] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Suggest category from host.
	 *
	 * @param string $host Hostname.
	 * @return string
	 */
	private function suggest_category( $host ) {
		$host = strtolower( $host );
		if ( false !== strpos( $host, 'analytics' ) || false !== strpos( $host, 'matomo' ) ) {
			return 'statistics';
		}
		if ( false !== strpos( $host, 'doubleclick' ) || false !== strpos( $host, 'facebook' ) || false !== strpos( $host, 'ads' ) || false !== strpos( $host, 'youtube' ) || false !== strpos( $host, 'vimeo' ) ) {
			return 'marketing';
		}
		return 'unclassified';
	}

	/**
	 * Convert structured report to old-style findings.
	 *
	 * @param array $report Structured report.
	 * @return array
	 */
	private function legacy_findings( $report ) {
		$findings = array();
		foreach ( $report['cookies'] as $cookie ) {
			$findings[] = 'Set-Cookie: ' . $cookie['name'];
		}
		foreach ( $report['resources'] as $resource ) {
			$findings[] = 'External resource: ' . $resource['host'];
		}
		return $findings ? array_values( array_unique( $findings ) ) : array( 'No Set-Cookie headers or external script/embed hosts found.' );
	}
}
