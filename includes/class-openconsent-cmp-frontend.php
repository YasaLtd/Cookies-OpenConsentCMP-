<?php
/**
 * Frontend rendering, script registration, and consent signals.
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend controller.
 */
final class OpenConsent_CMP_Frontend {
	/**
	 * Core plugin instance.
	 *
	 * @var OpenConsent_CMP
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param OpenConsent_CMP $plugin Core plugin instance.
	 */
	public function __construct( OpenConsent_CMP $plugin ) {
		$this->plugin = $plugin;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'print_early_consent_script' ), 0 );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 10, 3 );
		add_filter( 'the_content', array( $this, 'filter_content_embeds' ), 20 );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		$options = $this->plugin->options();
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$site_locale      = str_replace( '_', '-', get_locale() );
		$detected_locale  = function_exists( 'determine_locale' ) ? str_replace( '_', '-', determine_locale() ) : $site_locale;
		$banner_language = $options['banner_language'];
		if ( 'site' === $banner_language ) {
			$detected_locale = $site_locale;
		} elseif ( ! in_array( $banner_language, array( 'auto', 'site' ), true ) ) {
			$detected_locale = $banner_language;
		}

		$theme_presets = OpenConsent_CMP::theme_presets();
		$theme         = isset( $theme_presets[ $options['theme'] ] ) ? $options['theme'] : OpenConsent_CMP::defaults()['theme'];
		$theme_preset  = $theme_presets[ $theme ];

		wp_enqueue_style(
			'openconsent-cmp',
			OPENCONSENT_CMP_URL . 'assets/css/openconsent-cmp.css',
			array(),
			OPENCONSENT_CMP_VERSION
		);

		wp_enqueue_script(
			'openconsent-cmp',
			OPENCONSENT_CMP_URL . 'assets/js/openconsent-cmp.js',
			array(),
			OPENCONSENT_CMP_VERSION,
			false
		);

		wp_localize_script(
			'openconsent-cmp',
			'OpenConsentCMP',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'openconsent-cmp' ),
				'blockingMode'      => $options['blocking_mode'],
				'googleConsentMode' => ! empty( $options['google_consent_mode'] ),
				'googleConsentBehavior' => $options['google_consent_behavior'],
				'googleSignalMap'   => array(
					'ad_storage'              => $options['google_signal_map_ad_storage'],
					'ad_user_data'            => $options['google_signal_map_ad_user_data'],
					'ad_personalization'      => $options['google_signal_map_ad_personalization'],
					'analytics_storage'       => $options['google_signal_map_analytics_storage'],
					'functionality_storage'   => $options['google_signal_map_functionality_storage'],
					'personalization_storage' => $options['google_signal_map_personalization_storage'],
				),
				'urlPassthrough'    => ! empty( $options['url_passthrough'] ),
				'adsDataRedaction'  => ! empty( $options['ads_data_redaction'] ),
				'wpConsentApi'      => ! empty( $options['wp_consent_api'] ),
				'debugMode'         => ! empty( $options['debug_mode'] ),
				'autoDetectLanguage' => ! empty( $options['auto_detect_language'] ) && 'auto' === $banner_language,
				'detectedLanguage'   => $detected_locale,
				'siteLocale'        => $site_locale,
				'languageMode'      => $banner_language,
				'regionMode'        => $options['region_mode'],
				'defaultRegion'     => $options['default_region'],
				'consentModel'      => $options['consent_model'],
				'services'          => $this->plugin->services(),
				'ui'                => array(
					'title'        => $options['banner_title'],
					'message'      => $options['banner_message'],
					'partyDisclosure' => $options['party_disclosure'],
					'privacyUrl'   => $options['privacy_url'],
					'accept'       => $options['button_accept'],
					'reject'       => $options['button_reject'],
					'save'         => $options['button_save'],
					'revoke'       => $options['button_revoke'],
					'position'     => $options['position'],
					'theme'        => $theme,
					'accent'       => $theme_preset['accent'],
					'background'   => $theme_preset['background'],
					'text'         => $theme_preset['text'],
					'descriptions' => array(
						'necessary'   => __( 'Necessary cookies keep the site secure and working. They are always active.', 'openconsent-cmp' ),
						'preferences' => $options['category_preferences'],
						'statistics'  => $options['category_statistics'],
						'marketing'   => $options['category_marketing'],
						'unclassified' => $options['category_unclassified'],
					),
				),
				'defaultUi'          => array(
					'title'        => OpenConsent_CMP::defaults()['banner_title'],
					'message'      => OpenConsent_CMP::defaults()['banner_message'],
					'partyDisclosure' => OpenConsent_CMP::defaults()['party_disclosure'],
					'accept'       => OpenConsent_CMP::defaults()['button_accept'],
					'reject'       => OpenConsent_CMP::defaults()['button_reject'],
					'save'         => OpenConsent_CMP::defaults()['button_save'],
					'revoke'       => OpenConsent_CMP::defaults()['button_revoke'],
					'descriptions' => array(
						'necessary'   => __( 'Necessary cookies keep the site secure and working. They are always active.', 'openconsent-cmp' ),
						'preferences' => OpenConsent_CMP::defaults()['category_preferences'],
						'statistics'  => OpenConsent_CMP::defaults()['category_statistics'],
						'marketing'   => OpenConsent_CMP::defaults()['category_marketing'],
						'unclassified' => OpenConsent_CMP::defaults()['category_unclassified'],
					),
				),
			)
		);
	}

	/**
	 * Print early script for Google Consent Mode defaults and automatic blocking.
	 *
	 * @return void
	 */
	public function print_early_consent_script() {
		$options = $this->plugin->options();
		if ( empty( $options['enabled'] ) ) {
			return;
		}

		$services = wp_json_encode( $this->plugin->services() );
		$signal_map = wp_json_encode(
			array(
				'ad_storage'              => $options['google_signal_map_ad_storage'],
				'ad_user_data'            => $options['google_signal_map_ad_user_data'],
				'ad_personalization'      => $options['google_signal_map_ad_personalization'],
				'analytics_storage'       => $options['google_signal_map_analytics_storage'],
				'functionality_storage'   => $options['google_signal_map_functionality_storage'],
				'personalization_storage' => $options['google_signal_map_personalization_storage'],
			)
		);
		?>
		<script id="openconsent-cmp-early">
		(function () {
			window.OpenConsentQueue = window.OpenConsentQueue || [];
			window.OpenConsentServices = <?php echo $services; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			<?php if ( ! empty( $options['wp_consent_api'] ) ) : ?>
			window.wp_consent_type = '<?php echo esc_js( $this->wp_consent_type_for_options( $options ) ); ?>';
			try {
				document.dispatchEvent(new CustomEvent('wp_consent_type_defined', { detail: { consent_type: window.wp_consent_type, source: 'openconsent-cmp' } }));
			} catch (error) {}
			<?php endif; ?>
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
			<?php if ( ! empty( $options['google_consent_mode'] ) ) : ?>
			var openConsentSignalMap = <?php echo $signal_map; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			function openConsentGoogleState(consent) {
				var state = { security_storage: 'granted' };
				Object.keys(openConsentSignalMap).forEach(function (signal) {
					var category = openConsentSignalMap[signal];
					state[signal] = category && category !== 'denied' && consent && consent[category] ? 'granted' : 'denied';
				});
				return state;
			}
			window.gtag('consent', 'default', {
				ad_personalization: 'denied',
				ad_storage: 'denied',
				ad_user_data: 'denied',
				analytics_storage: 'denied',
				functionality_storage: 'denied',
				personalization_storage: 'denied',
				security_storage: 'granted',
				wait_for_update: 500
			});
			window.gtag('set', 'ads_data_redaction', <?php echo ! empty( $options['ads_data_redaction'] ) ? 'true' : 'false'; ?>);
			window.gtag('set', 'url_passthrough', <?php echo ! empty( $options['url_passthrough'] ) ? 'true' : 'false'; ?>);
			try {
				var match = document.cookie.match(/(?:^|; )openconsent_cmp=([^;]*)/);
				if (match) {
					var consent = JSON.parse(decodeURIComponent(match[1]));
					window.gtag('consent', 'update', openConsentGoogleState(consent));
				}
			} catch (error) {}
			<?php endif; ?>
		}());
		</script>
		<?php
	}

	/**
	 * Convert registered scripts to manual consent scripts when matching configured services.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public function filter_script_tag( $tag, $handle, $src ) {
		$options = $this->plugin->options();
		if ( empty( $options['enabled'] ) || 'auto' !== $options['blocking_mode'] || '' === $src ) {
			return $tag;
		}

		$service = $this->match_handle( $handle );

		if ( ! $service && ! empty( $options['google_consent_mode'] ) && 'advanced' === $options['google_consent_behavior'] && $this->is_google_consent_aware_url( $src ) ) {
			return $tag;
		}

		if ( ! $service ) {
			$service = $this->match_service( $src );
		}

		if ( ! $service ) {
			return $tag;
		}

		$attrs = sprintf(
			'type="text/plain" data-openconsent-category="%s" data-openconsent-src="%s" data-openconsent-handle="%s" data-openconsent-service="%s"',
			esc_attr( $service['category'] ),
			esc_url( $src ),
			esc_attr( $handle ),
			esc_attr( $service['name'] )
		);

		return preg_replace( '/<script\b[^>]*src=(["\']).*?\1[^>]*><\/script>/i', '<script ' . $attrs . '></script>', $tag ) ?: $tag;
	}

	/**
	 * Replace matching iframe sources until the visitor grants the mapped category.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_content_embeds( $content ) {
		$options = $this->plugin->options();
		if ( empty( $options['enabled'] ) || empty( $options['block_iframes'] ) || 'auto' !== $options['blocking_mode'] || false === stripos( $content, '<iframe' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<iframe\b[^>]*>/i',
			function ( $matches ) {
				$tag = $matches[0];
				if ( false !== stripos( $tag, 'data-openconsent-category=' ) ) {
					return $tag;
				}

				if ( ! preg_match( '/\ssrc=(["\'])(.*?)\1/i', $tag, $src_match ) ) {
					return $tag;
				}

				$src     = html_entity_decode( $src_match[2], ENT_QUOTES, get_bloginfo( 'charset' ) );
				$service = $this->match_service( $src );
				if ( ! $service || $this->visitor_has_consent( $service['category'] ) ) {
					return $tag;
				}

				$clean = preg_replace( '/\s(?:src|srcdoc|data-openconsent-[a-z-]+)=(["\']).*?\1/i', '', $tag );
				$attrs = sprintf(
					' src="about:blank" srcdoc="%s" data-openconsent-category="%s" data-openconsent-src="%s" data-openconsent-service="%s" data-openconsent-provider="%s" data-openconsent-purpose="%s" data-openconsent-blocked="1"',
					esc_attr__( 'This embed is blocked until you allow its cookie category.', 'openconsent-cmp' ),
					esc_attr( $service['category'] ),
					esc_url( $src ),
					esc_attr( $service['name'] ),
					esc_attr( $service['provider'] ?? '' ),
					esc_attr( $service['purpose'] ?? '' )
				);

				return preg_replace( '/\s*>$/', $attrs . '>', $clean ) ?: $tag;
			},
			$content
		);
	}

	/**
	 * Match a URL to a consent category.
	 *
	 * @param string $src Script URL.
	 * @return string|false
	 */
	private function match_category( $src ) {
		$service = $this->match_service( $src );
		return $service ? $service['category'] : false;
	}

	/**
	 * Match a registered WordPress script handle to a consent service.
	 *
	 * @param string $handle Script handle.
	 * @return array|false
	 */
	private function match_handle( $handle ) {
		$handle = sanitize_key( $handle );
		if ( '' === $handle ) {
			return false;
		}

		foreach ( $this->plugin->script_handles() as $service ) {
			if ( $handle === $service['handle'] ) {
				return $service;
			}
		}

		return false;
	}

	/**
	 * Match a URL to a configured service.
	 *
	 * @param string $src Resource URL.
	 * @return array|false
	 */
	private function match_service( $src ) {
		foreach ( $this->plugin->services() as $service ) {
			if ( false !== stripos( $src, $service['pattern'] ) ) {
				return $service;
			}
		}

		return false;
	}

	/**
	 * Check whether the current request already carries consent for a category.
	 *
	 * @param string $category Consent category.
	 * @return bool
	 */
	private function visitor_has_consent( $category ) {
		if ( 'necessary' === $category ) {
			return true;
		}

		if ( empty( $_COOKIE['openconsent_cmp'] ) ) {
			return false;
		}

		$raw     = sanitize_text_field( wp_unslash( $_COOKIE['openconsent_cmp'] ) );
		$consent = json_decode( rawurldecode( $raw ), true );
		return is_array( $consent ) && ! empty( $consent[ $category ] );
	}

	/**
	 * Return whether a URL is a Google tag URL that can operate under Consent Mode.
	 *
	 * @param string $src Script URL.
	 * @return bool
	 */
	private function is_google_consent_aware_url( $src ) {
		$host = wp_parse_url( $src, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}

		$host = strtolower( $host );
		return false !== strpos( $host, 'googletagmanager.com' )
			|| false !== strpos( $host, 'google-analytics.com' )
			|| false !== strpos( $host, 'googleadservices.com' )
			|| false !== strpos( $host, 'doubleclick.net' );
	}

	/**
	 * Map plugin options to the WP Consent API consent type.
	 *
	 * @param array $options Plugin options.
	 * @return string
	 */
	private function wp_consent_type_for_options( $options ) {
		return 'opt_out' === $options['consent_model'] && 'strict' !== $options['region_mode'] ? 'optout' : 'optin';
	}
}
