<?php
/**
 * Admin UI and settings.
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin controller.
 */
final class OpenConsent_CMP_Admin {
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

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_openconsent_run_scan', array( $this, 'run_scan' ) );
		add_action( 'admin_post_openconsent_export_logs', array( $this, 'export_logs' ) );
	}

	/**
	 * Register admin page.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'OpenConsent CMP', 'openconsent-cmp' ),
			__( 'OpenConsent CMP', 'openconsent-cmp' ),
			'manage_options',
			'openconsent-cmp',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'openconsent_cmp',
			OpenConsent_CMP::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'default'           => OpenConsent_CMP::defaults(),
			)
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$current = $this->plugin->options();
		$input   = is_array( $input ) ? $input : array();

		$options = array(
			'enabled'             => empty( $input['enabled'] ) ? 0 : 1,
			'blocking_mode'       => isset( $input['blocking_mode'] ) && 'manual' === $input['blocking_mode'] ? 'manual' : 'auto',
			'banner_title'        => sanitize_text_field( $input['banner_title'] ?? '' ),
			'banner_message'      => sanitize_textarea_field( $input['banner_message'] ?? '' ),
			'party_disclosure'    => sanitize_textarea_field( $input['party_disclosure'] ?? '' ),
			'privacy_url'         => esc_url_raw( $input['privacy_url'] ?? '' ),
			'button_accept'       => sanitize_text_field( $input['button_accept'] ?? '' ),
			'button_reject'       => sanitize_text_field( $input['button_reject'] ?? '' ),
			'button_save'         => sanitize_text_field( $input['button_save'] ?? '' ),
			'button_customize'    => sanitize_text_field( $input['button_customize'] ?? '' ),
			'button_revoke'       => sanitize_text_field( $input['button_revoke'] ?? '' ),
			'auto_detect_language' => empty( $input['auto_detect_language'] ) ? 0 : 1,
			'banner_language'     => in_array( $input['banner_language'] ?? '', array( 'auto', 'site', 'en', 'fi', 'de', 'es', 'fr', 'it', 'nl', 'sv' ), true ) ? $input['banner_language'] : 'auto',
			'region_mode'         => in_array( $input['region_mode'] ?? '', array( 'strict', 'auto', 'notice' ), true ) ? $input['region_mode'] : 'strict',
			'default_region'      => in_array( $input['default_region'] ?? '', array( 'eea', 'us', 'other' ), true ) ? $input['default_region'] : 'eea',
			'consent_model'       => in_array( $input['consent_model'] ?? '', array( 'opt_in', 'opt_out' ), true ) ? $input['consent_model'] : 'opt_in',
			'position'            => isset( $input['position'] ) && 'bottom' === $input['position'] ? 'bottom' : 'center',
			'accent_color'        => sanitize_hex_color( $input['accent_color'] ?? '' ) ?: '#54d2bf',
			'background_color'    => sanitize_hex_color( $input['background_color'] ?? '' ) ?: '#111827',
			'text_color'          => sanitize_hex_color( $input['text_color'] ?? '' ) ?: '#ffffff',
			'google_consent_mode' => empty( $input['google_consent_mode'] ) ? 0 : 1,
			'google_consent_behavior' => isset( $input['google_consent_behavior'] ) && 'basic' === $input['google_consent_behavior'] ? 'basic' : 'advanced',
			'google_signal_ad_storage' => empty( $input['google_signal_ad_storage'] ) ? 0 : 1,
			'google_signal_ad_user_data' => empty( $input['google_signal_ad_user_data'] ) ? 0 : 1,
			'google_signal_ad_personalization' => empty( $input['google_signal_ad_personalization'] ) ? 0 : 1,
			'google_signal_analytics_storage' => empty( $input['google_signal_analytics_storage'] ) ? 0 : 1,
			'google_signal_functionality_storage' => empty( $input['google_signal_functionality_storage'] ) ? 0 : 1,
			'google_signal_personalization_storage' => empty( $input['google_signal_personalization_storage'] ) ? 0 : 1,
			'url_passthrough'     => empty( $input['url_passthrough'] ) ? 0 : 1,
			'ads_data_redaction'  => empty( $input['ads_data_redaction'] ) ? 0 : 1,
			'log_retention_days'  => max( 1, absint( $input['log_retention_days'] ?? 365 ) ),
			'services'            => $this->sanitize_services( $input['services'] ?? '' ),
		);

		$options['scan_report']           = $current['scan_report'];
		$options['scan_report_generated'] = $current['scan_report_generated'];

		foreach ( array( 'category_preferences', 'category_statistics', 'category_marketing', 'category_unclassified' ) as $key ) {
			$options[ $key ] = sanitize_textarea_field( $input[ $key ] ?? '' );
		}

		return wp_parse_args( $options, OpenConsent_CMP::defaults() );
	}

	/**
	 * Sanitize service registry textarea.
	 *
	 * @param string $services Raw service lines.
	 * @return string
	 */
	private function sanitize_services( $services ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $services );
		$clean = array();

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', sanitize_text_field( $line ) ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}

			$category = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ? $parts[1] : 'unclassified';
			$name     = isset( $parts[2] ) ? $parts[2] : $parts[0];
			$clean[]  = "{$parts[0]}|{$category}|{$name}";
		}

		return implode( "\n", $clean );
	}

	/**
	 * Run scanner action.
	 *
	 * @return void
	 */
	public function run_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to scan this site.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_run_scan' );

		$options = $this->plugin->options();
		$report  = $this->plugin->scanner->scan_homepage();

		$options['scan_report']           = $report;
		$options['scan_report_generated'] = current_time( 'mysql' );
		update_option( OpenConsent_CMP::OPTION, $options );

		wp_safe_redirect( admin_url( 'options-general.php?page=openconsent-cmp&openconsent_scanned=1' ) );
		exit;
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = $this->plugin->options();
		$logs    = $this->recent_logs();
		$services = $this->plugin->services();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'OpenConsent CMP', 'openconsent-cmp' ); ?></h1>
			<p><?php esc_html_e( 'A self-hosted consent manager for WordPress: present clear choices, categorize services, block optional scripts, publish a declaration, record consent choices, and send Google Consent Mode signals.', 'openconsent-cmp' ); ?></p>
			<style>
				.openconsent-admin-grid{display:grid;gap:14px;grid-template-columns:repeat(4,minmax(0,1fr));margin:18px 0 22px}.openconsent-admin-card{background:#fff;border:1px solid #dcdcde;border-left:4px solid #2271b1;border-radius:4px;padding:14px}.openconsent-admin-card strong{display:block;font-size:22px;line-height:1.2}.openconsent-admin-card span{color:#646970;display:block;margin-top:4px}.openconsent-settings-card{background:#fff;border:1px solid #dcdcde;border-radius:4px;margin:18px 0;padding:1px 18px 18px}.openconsent-button-grid{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(120px,1fr));max-width:980px}.openconsent-button-grid label{font-weight:600}.openconsent-button-grid input{margin-top:4px;width:100%}.openconsent-help-list{margin:8px 0 0 18px}.openconsent-help-list code{background:#f6f7f7}@media(max-width:1100px){.openconsent-admin-grid,.openconsent-button-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.openconsent-admin-grid,.openconsent-button-grid{grid-template-columns:1fr}}
			</style>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Publisher ads note:', 'openconsent-cmp' ); ?></strong> <?php esc_html_e( 'Google requires a Google-certified CMP integrated with the IAB TCF when serving personalized AdSense, Ad Manager, or AdMob ads to users in the EEA, UK, or Switzerland. OpenConsent CMP is self-hosted and is not a Google-certified TCF CMP. Use it for publisher ads only after reviewing your ad mode, regions, and legal requirements.', 'openconsent-cmp' ); ?></p>
			</div>

			<?php if ( isset( $_GET['openconsent_scanned'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Homepage scan completed.', 'openconsent-cmp' ); ?></p></div>
			<?php endif; ?>

			<div class="openconsent-admin-grid" aria-label="<?php esc_attr_e( 'OpenConsent CMP status', 'openconsent-cmp' ); ?>">
				<div class="openconsent-admin-card"><strong><?php echo ! empty( $options['enabled'] ) ? esc_html__( 'On', 'openconsent-cmp' ) : esc_html__( 'Off', 'openconsent-cmp' ); ?></strong><span><?php esc_html_e( 'Frontend banner', 'openconsent-cmp' ); ?></span></div>
				<div class="openconsent-admin-card"><strong><?php echo esc_html( count( $services ) ); ?></strong><span><?php esc_html_e( 'Configured services', 'openconsent-cmp' ); ?></span></div>
				<div class="openconsent-admin-card"><strong><?php echo esc_html( count( $logs ) ); ?></strong><span><?php esc_html_e( 'Recent consent records', 'openconsent-cmp' ); ?></span></div>
				<div class="openconsent-admin-card"><strong><?php echo ! empty( $options['auto_detect_language'] ) ? esc_html__( 'Auto', 'openconsent-cmp' ) : esc_html__( 'Site', 'openconsent-cmp' ); ?></strong><span><?php esc_html_e( 'Language mode', 'openconsent-cmp' ); ?></span></div>
			</div>

			<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Setup checklist', 'openconsent-cmp' ); ?></h2>
				<ul class="openconsent-help-list">
					<li><?php esc_html_e( 'Add every analytics, advertising, embed, and preference service to the service registry.', 'openconsent-cmp' ); ?></li>
					<li><?php esc_html_e( 'Place the shortcode on your cookie policy page:', 'openconsent-cmp' ); ?> <code>[openconsent_declaration]</code></li>
					<li><?php esc_html_e( 'Link your privacy policy and describe parties that may receive personal data.', 'openconsent-cmp' ); ?></li>
					<li><?php esc_html_e( 'Test the banner in an incognito window before relying on it in production.', 'openconsent-cmp' ); ?></li>
				</ul>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'openconsent_cmp' ); ?>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Banner', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable banner', 'openconsent-cmp' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[enabled]" value="1" <?php checked( $options['enabled'], 1 ); ?>> <?php esc_html_e( 'Show consent banner on the frontend', 'openconsent-cmp' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-title"><?php esc_html_e( 'Title', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-title" class="regular-text" type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[banner_title]" value="<?php echo esc_attr( $options['banner_title'] ); ?>" placeholder="<?php esc_attr_e( 'Your privacy choices', 'openconsent-cmp' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-message"><?php esc_html_e( 'Message', 'openconsent-cmp' ); ?></label></th>
						<td><textarea id="openconsent-message" class="large-text" rows="3" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[banner_message]"><?php echo esc_textarea( $options['banner_message'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-party-disclosure"><?php esc_html_e( 'Party disclosure', 'openconsent-cmp' ); ?></label></th>
						<td>
							<textarea id="openconsent-party-disclosure" class="large-text" rows="3" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[party_disclosure]"><?php echo esc_textarea( $options['party_disclosure'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Use this to identify parties that may collect, receive, or use personal data when Google products or other services are enabled.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-privacy"><?php esc_html_e( 'Privacy policy URL', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-privacy" class="regular-text" type="url" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[privacy_url]" value="<?php echo esc_attr( $options['privacy_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button labels', 'openconsent-cmp' ); ?></th>
						<td>
							<div class="openconsent-button-grid">
								<label><?php esc_html_e( 'Accept all', 'openconsent-cmp' ); ?><input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_accept]" value="<?php echo esc_attr( $options['button_accept'] ); ?>"></label>
								<label><?php esc_html_e( 'Necessary only', 'openconsent-cmp' ); ?><input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_reject]" value="<?php echo esc_attr( $options['button_reject'] ); ?>"></label>
								<label><?php esc_html_e( 'Save choices', 'openconsent-cmp' ); ?><input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_save]" value="<?php echo esc_attr( $options['button_save'] ); ?>"></label>
								<label><?php esc_html_e( 'Theme selector', 'openconsent-cmp' ); ?><input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_customize]" value="<?php echo esc_attr( $options['button_customize'] ); ?>"></label>
								<label><?php esc_html_e( 'Reopen control', 'openconsent-cmp' ); ?><input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_revoke]" value="<?php echo esc_attr( $options['button_revoke'] ); ?>"></label>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Language', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[auto_detect_language]" value="1" <?php checked( $options['auto_detect_language'], 1 ); ?>> <?php esc_html_e( 'Allow browser-language detection when language mode is Auto', 'openconsent-cmp' ); ?></label><br>
							<select name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[banner_language]">
								<option value="auto" <?php selected( $options['banner_language'], 'auto' ); ?>><?php esc_html_e( 'Auto: visitor browser language', 'openconsent-cmp' ); ?></option>
								<option value="site" <?php selected( $options['banner_language'], 'site' ); ?>><?php esc_html_e( 'WordPress site language', 'openconsent-cmp' ); ?></option>
								<option value="en" <?php selected( $options['banner_language'], 'en' ); ?>>English</option>
								<option value="fi" <?php selected( $options['banner_language'], 'fi' ); ?>>Suomi</option>
								<option value="de" <?php selected( $options['banner_language'], 'de' ); ?>>Deutsch</option>
								<option value="es" <?php selected( $options['banner_language'], 'es' ); ?>>Espa&ntilde;ol</option>
								<option value="fr" <?php selected( $options['banner_language'], 'fr' ); ?>>Fran&ccedil;ais</option>
								<option value="it" <?php selected( $options['banner_language'], 'it' ); ?>>Italiano</option>
								<option value="nl" <?php selected( $options['banner_language'], 'nl' ); ?>>Nederlands</option>
								<option value="sv" <?php selected( $options['banner_language'], 'sv' ); ?>>Svenska</option>
							</select>
							<p class="description"><?php esc_html_e( 'Custom text stays editable and is rendered as normal page text, so browser translation tools can translate it.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Region behavior', 'openconsent-cmp' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[region_mode]">
								<option value="strict" <?php selected( $options['region_mode'], 'strict' ); ?>><?php esc_html_e( 'Strict opt-in for all visitors', 'openconsent-cmp' ); ?></option>
								<option value="auto" <?php selected( $options['region_mode'], 'auto' ); ?>><?php esc_html_e( 'Auto-detect region, strict for EEA/UK/CH', 'openconsent-cmp' ); ?></option>
								<option value="notice" <?php selected( $options['region_mode'], 'notice' ); ?>><?php esc_html_e( 'Notice-only mode for non-regulated traffic', 'openconsent-cmp' ); ?></option>
							</select>
							<select name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[default_region]">
								<option value="eea" <?php selected( $options['default_region'], 'eea' ); ?>><?php esc_html_e( 'Default: EEA / UK / Switzerland', 'openconsent-cmp' ); ?></option>
								<option value="us" <?php selected( $options['default_region'], 'us' ); ?>><?php esc_html_e( 'Default: United States', 'openconsent-cmp' ); ?></option>
								<option value="other" <?php selected( $options['default_region'], 'other' ); ?>><?php esc_html_e( 'Default: Other region', 'openconsent-cmp' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Auto-detection uses browser language and time zone hints. It is not legal geolocation and should be reviewed for your audience.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Consent model', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[consent_model]" value="opt_in" <?php checked( $options['consent_model'], 'opt_in' ); ?>> <?php esc_html_e( 'Opt-in: block optional categories until consent', 'openconsent-cmp' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[consent_model]" value="opt_out" <?php checked( $options['consent_model'], 'opt_out' ); ?>> <?php esc_html_e( 'Opt-out: preselect optional categories for non-strict regions', 'openconsent-cmp' ); ?></label>
							<p class="description"><?php esc_html_e( 'Strict regions always start with optional categories denied. Choose opt-out only after legal review.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Layout and colors', 'openconsent-cmp' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[position]">
								<option value="bottom" <?php selected( $options['position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom banner', 'openconsent-cmp' ); ?></option>
								<option value="center" <?php selected( $options['position'], 'center' ); ?>><?php esc_html_e( 'Centered dialog', 'openconsent-cmp' ); ?></option>
							</select>
							<input type="color" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>">
							<input type="color" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[background_color]" value="<?php echo esc_attr( $options['background_color'] ); ?>">
							<input type="color" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[text_color]" value="<?php echo esc_attr( $options['text_color'] ); ?>">
						</td>
					</tr>
				</table>
				</div>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Blocking and signals', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Blocking mode', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[blocking_mode]" value="auto" <?php checked( $options['blocking_mode'], 'auto' ); ?>> <?php esc_html_e( 'Automatic client-side blocking by URL pattern', 'openconsent-cmp' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[blocking_mode]" value="manual" <?php checked( $options['blocking_mode'], 'manual' ); ?>> <?php esc_html_e( 'Manual markup only', 'openconsent-cmp' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Google Consent Mode v2', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_consent_mode]" value="1" <?php checked( $options['google_consent_mode'], 1 ); ?>> <?php esc_html_e( 'Emit default denied state and update on consent', 'openconsent-cmp' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_consent_behavior]" value="advanced" <?php checked( $options['google_consent_behavior'], 'advanced' ); ?>> <?php esc_html_e( 'Advanced: allow Google consent-aware tags to load with denied defaults', 'openconsent-cmp' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_consent_behavior]" value="basic" <?php checked( $options['google_consent_behavior'], 'basic' ); ?>> <?php esc_html_e( 'Basic: block Google tags until consent is granted', 'openconsent-cmp' ); ?></label><br>
							<p class="description"><?php esc_html_e( 'AdSense and other Google publisher ad tags are kept in the marketing category by default. Personalized publisher ads for EEA, UK, and Switzerland traffic require a certified TCF CMP.', 'openconsent-cmp' ); ?></p>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Google consent signals', 'openconsent-cmp' ); ?></legend>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_ad_storage]" value="1" <?php checked( $options['google_signal_ad_storage'], 1 ); ?>> <code>ad_storage</code> <?php esc_html_e( 'Marketing storage for ads cookies.', 'openconsent-cmp' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_ad_user_data]" value="1" <?php checked( $options['google_signal_ad_user_data'], 1 ); ?>> <code>ad_user_data</code> <?php esc_html_e( 'Send user data to Google for ads.', 'openconsent-cmp' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_ad_personalization]" value="1" <?php checked( $options['google_signal_ad_personalization'], 1 ); ?>> <code>ad_personalization</code> <?php esc_html_e( 'Personalized ads and remarketing.', 'openconsent-cmp' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_analytics_storage]" value="1" <?php checked( $options['google_signal_analytics_storage'], 1 ); ?>> <code>analytics_storage</code> <?php esc_html_e( 'Analytics cookies and measurement.', 'openconsent-cmp' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_functionality_storage]" value="1" <?php checked( $options['google_signal_functionality_storage'], 1 ); ?>> <code>functionality_storage</code> <?php esc_html_e( 'Functional storage linked to preference choices.', 'openconsent-cmp' ); ?></label><br>
								<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[google_signal_personalization_storage]" value="1" <?php checked( $options['google_signal_personalization_storage'], 1 ); ?>> <code>personalization_storage</code> <?php esc_html_e( 'Personalization storage linked to preference choices.', 'openconsent-cmp' ); ?></label>
							</fieldset>
							<p class="description"><?php esc_html_e( 'OpenConsent always sends security_storage as granted. Disabled signals are kept denied instead of being granted by a category choice.', 'openconsent-cmp' ); ?></p>
							<p class="description"><a href="https://developers.google.com/tag-platform/security/guides/consent" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Consent Mode setup guide', 'openconsent-cmp' ); ?></a> | <a href="https://www.google.com/about/company/user-consent-policy/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google EU user consent policy', 'openconsent-cmp' ); ?></a></p>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[ads_data_redaction]" value="1" <?php checked( $options['ads_data_redaction'], 1 ); ?>> <?php esc_html_e( 'Enable ads data redaction', 'openconsent-cmp' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[url_passthrough]" value="1" <?php checked( $options['url_passthrough'], 1 ); ?>> <?php esc_html_e( 'Enable URL passthrough', 'openconsent-cmp' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-services"><?php esc_html_e( 'Service registry', 'openconsent-cmp' ); ?></label></th>
						<td>
							<textarea id="openconsent-services" class="large-text code" rows="9" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[services]"><?php echo esc_textarea( $options['services'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One service per line: URL pattern | category | display name. Categories: preferences, statistics, marketing, unclassified.', 'openconsent-cmp' ); ?></p>
							<p class="description"><?php esc_html_e( 'Examples:', 'openconsent-cmp' ); ?> <code>analytics.example.com|statistics|Analytics tool</code> <code>unknown.example.com|unclassified|Review needed</code></p>
						</td>
					</tr>
				</table>
				</div>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Category descriptions', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( array( 'preferences', 'statistics', 'marketing', 'unclassified' ) as $category ) : ?>
						<tr>
							<th scope="row"><label for="openconsent-<?php echo esc_attr( $category ); ?>"><?php echo esc_html( ucfirst( $category ) ); ?></label></th>
							<td><textarea id="openconsent-<?php echo esc_attr( $category ); ?>" class="large-text" rows="2" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[category_<?php echo esc_attr( $category ); ?>]"><?php echo esc_textarea( $options[ 'category_' . $category ] ); ?></textarea></td>
						</tr>
					<?php endforeach; ?>
				</table>
				</div>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Audit log', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="openconsent-retention"><?php esc_html_e( 'Retention', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-retention" type="number" min="1" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[log_retention_days]" value="<?php echo esc_attr( $options['log_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'openconsent-cmp' ); ?></td>
					</tr>
				</table>
				</div>

				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Local scan', 'openconsent-cmp' ); ?></h2>
			<p><?php esc_html_e( 'The scanner checks the homepage for Set-Cookie headers and external script, iframe, and image hosts. It is a lightweight local inventory tool and does not run JavaScript like a full browser crawler.', 'openconsent-cmp' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="openconsent_run_scan">
				<?php wp_nonce_field( 'openconsent_run_scan' ); ?>
				<?php submit_button( __( 'Scan homepage', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php if ( ! empty( $options['scan_report_generated'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Last scan:', 'openconsent-cmp' ); ?></strong> <?php echo esc_html( $options['scan_report_generated'] ); ?></p>
				<ul>
					<?php foreach ( (array) $options['scan_report'] as $item ) : ?>
						<li><code><?php echo esc_html( $item ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Recent consent logs', 'openconsent-cmp' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0 0 12px;">
				<input type="hidden" name="action" value="openconsent_export_logs">
				<?php wp_nonce_field( 'openconsent_export_logs' ); ?>
				<?php submit_button( __( 'Export consent logs CSV', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Time', 'openconsent-cmp' ); ?></th><th><?php esc_html_e( 'Consent ID', 'openconsent-cmp' ); ?></th><th><?php esc_html_e( 'Consent', 'openconsent-cmp' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No consent logs yet.', 'openconsent-cmp' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->created_at ); ?></td>
								<td><code><?php echo esc_html( $log->consent_id ); ?></code></td>
								<td><code><?php echo esc_html( $log->consent_json ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<p><?php esc_html_e( 'Use shortcode [openconsent_declaration] on a Cookie Policy page to publish the declaration.', 'openconsent-cmp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get recent consent logs.
	 *
	 * @return array
	 */
	private function recent_logs() {
		global $wpdb;
		$table = $wpdb->prefix . OpenConsent_CMP::LOG_TABLE;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array();
		}

		return $wpdb->get_results( "SELECT created_at, consent_id, consent_json FROM {$table} ORDER BY id DESC LIMIT 20" );
	}

	/**
	 * Export consent logs as CSV for site owner records.
	 *
	 * @return void
	 */
	public function export_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export consent logs.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_export_logs' );

		global $wpdb;
		$table = $wpdb->prefix . OpenConsent_CMP::LOG_TABLE;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			wp_die( esc_html__( 'Consent log table does not exist yet.', 'openconsent-cmp' ) );
		}

		$rows = $wpdb->get_results( "SELECT created_at, consent_id, consent_json, consent_hash, ip_hash, user_agent_hash FROM {$table} ORDER BY id DESC", ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="openconsent-cmp-logs-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'created_at', 'consent_id', 'consent_json', 'consent_hash', 'ip_hash', 'user_agent_hash' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}
		fclose( $output );
		exit;
	}
}
