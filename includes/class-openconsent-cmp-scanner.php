<?php
/**
 * Simple local scanner.
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
	 * Scan the homepage for static cookie-related findings.
	 *
	 * @return array
	 */
	public function scan_homepage() {
		$response = wp_remote_get(
			home_url( '/' ),
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'user-agent'  => 'OpenConsent CMP Scanner/' . OPENCONSENT_CMP_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'Scan failed: ' . $response->get_error_message() );
		}

		$findings = array();
		$headers  = wp_remote_retrieve_headers( $response );
		$body     = wp_remote_retrieve_body( $response );

		if ( isset( $headers['set-cookie'] ) ) {
			$cookies = is_array( $headers['set-cookie'] ) ? $headers['set-cookie'] : array( $headers['set-cookie'] );
			foreach ( $cookies as $cookie ) {
				$findings[] = 'Set-Cookie: ' . $this->cookie_name( $cookie );
			}
		}

		$hosts = $this->extract_external_hosts( $body );
		foreach ( $hosts as $host ) {
			$findings[] = 'External resource: ' . $host;
		}

		$findings = array_values( array_unique( array_filter( $findings ) ) );

		if ( empty( $findings ) ) {
			$findings[] = 'No Set-Cookie headers or external script/embed hosts found on the homepage.';
		}

		return $findings;
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
	 * Extract external hosts from static markup.
	 *
	 * @param string $html HTML body.
	 * @return array
	 */
	private function extract_external_hosts( $html ) {
		$hosts = array();
		$home  = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! preg_match_all( '/<(script|iframe|img|link)\b[^>]+(?:src|href)=["\']([^"\']+)["\']/i', (string) $html, $matches ) ) {
			return $hosts;
		}

		foreach ( $matches[2] as $url ) {
			$host = wp_parse_url( html_entity_decode( $url ), PHP_URL_HOST );
			if ( $host && $home && strtolower( $host ) !== strtolower( $home ) ) {
				$hosts[] = sanitize_text_field( $host );
			}
		}

		sort( $hosts );

		return array_values( array_unique( $hosts ) );
	}
}
