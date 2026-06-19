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
				'urlPassthrough'    => ! empty( $options['url_passthrough'] ),
				'adsDataRedaction'  => ! empty( $options['ads_data_redaction'] ),
				'autoDetectLanguage' => ! empty( $options['auto_detect_language'] ),
				'detectedLanguage'   => str_replace( '_', '-', determine_locale() ),
				'siteLocale'        => str_replace( '_', '-', get_locale() ),
				'services'          => $this->plugin->services(),
				'ui'                => array(
					'title'        => $options['banner_title'],
					'message'      => $options['banner_message'],
					'partyDisclosure' => $options['party_disclosure'],
					'privacyUrl'   => $options['privacy_url'],
					'accept'       => $options['button_accept'],
					'reject'       => $options['button_reject'],
					'save'         => $options['button_save'],
					'customize'    => $options['button_customize'],
					'revoke'       => $options['button_revoke'],
					'position'     => $options['position'],
					'accent'       => $options['accent_color'],
					'background'   => $options['background_color'],
					'text'         => $options['text_color'],
					'descriptions' => array(
						'necessary'   => __( 'Necessary cookies keep the site secure and working. They are always active.', 'openconsent-cmp' ),
						'preferences' => $options['category_preferences'],
						'statistics'  => $options['category_statistics'],
						'marketing'   => $options['category_marketing'],
					),
				),
				'defaultUi'          => array(
					'title'        => OpenConsent_CMP::defaults()['banner_title'],
					'message'      => OpenConsent_CMP::defaults()['banner_message'],
					'partyDisclosure' => OpenConsent_CMP::defaults()['party_disclosure'],
					'accept'       => OpenConsent_CMP::defaults()['button_accept'],
					'reject'       => OpenConsent_CMP::defaults()['button_reject'],
					'save'         => OpenConsent_CMP::defaults()['button_save'],
					'customize'    => OpenConsent_CMP::defaults()['button_customize'],
					'revoke'       => OpenConsent_CMP::defaults()['button_revoke'],
					'descriptions' => array(
						'necessary'   => __( 'Necessary cookies keep the site secure and working. They are always active.', 'openconsent-cmp' ),
						'preferences' => OpenConsent_CMP::defaults()['category_preferences'],
						'statistics'  => OpenConsent_CMP::defaults()['category_statistics'],
						'marketing'   => OpenConsent_CMP::defaults()['category_marketing'],
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
		?>
		<script id="openconsent-cmp-early">
		(function () {
			window.OpenConsentQueue = window.OpenConsentQueue || [];
			window.OpenConsentServices = <?php echo $services; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			window.dataLayer = window.dataLayer || [];
			window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
			<?php if ( ! empty( $options['google_consent_mode'] ) ) : ?>
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
					window.gtag('consent', 'update', {
						ad_personalization: consent.marketing ? 'granted' : 'denied',
						ad_storage: consent.marketing ? 'granted' : 'denied',
						ad_user_data: consent.marketing ? 'granted' : 'denied',
						analytics_storage: consent.statistics ? 'granted' : 'denied',
						functionality_storage: consent.preferences ? 'granted' : 'denied',
						personalization_storage: consent.preferences ? 'granted' : 'denied',
						security_storage: 'granted'
					});
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

		if ( ! empty( $options['google_consent_mode'] ) && 'advanced' === $options['google_consent_behavior'] && $this->is_google_consent_aware_url( $src ) ) {
			return $tag;
		}

		$category = $this->match_category( $src );
		if ( ! $category ) {
			return $tag;
		}

		$attrs = sprintf(
			'type="text/plain" data-openconsent-category="%s" data-openconsent-src="%s" data-openconsent-handle="%s"',
			esc_attr( $category ),
			esc_url( $src ),
			esc_attr( $handle )
		);

		return preg_replace( '/<script\b[^>]*src=(["\']).*?\1[^>]*><\/script>/i', '<script ' . $attrs . '></script>', $tag ) ?: $tag;
	}

	/**
	 * Match a URL to a consent category.
	 *
	 * @param string $src Script URL.
	 * @return string|false
	 */
	private function match_category( $src ) {
		foreach ( $this->plugin->services() as $service ) {
			if ( false !== stripos( $src, $service['pattern'] ) ) {
				return $service['category'];
			}
		}

		return false;
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
}
