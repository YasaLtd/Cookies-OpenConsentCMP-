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
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_openconsent_run_scan', array( $this, 'run_scan' ) );
		add_action( 'admin_post_openconsent_export_logs', array( $this, 'export_logs' ) );
		add_action( 'admin_post_openconsent_export_logs_json', array( $this, 'export_logs_json' ) );
		add_action( 'admin_post_openconsent_prune_logs', array( $this, 'prune_logs' ) );
		add_action( 'admin_post_openconsent_export_settings', array( $this, 'export_settings' ) );
		add_action( 'admin_post_openconsent_export_services', array( $this, 'export_services' ) );
		add_action( 'admin_post_openconsent_import_settings', array( $this, 'import_settings' ) );
		add_action( 'admin_post_openconsent_import_services', array( $this, 'import_services' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
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
	 * Enqueue admin assets only on this settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_openconsent-cmp' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'openconsent-cmp-admin',
			OPENCONSENT_CMP_URL . 'assets/css/openconsent-cmp-admin.css',
			array(),
			OPENCONSENT_CMP_VERSION
		);
	}

	/**
	 * Show first-run setup notice after activation.
	 *
	 * @return void
	 */
	public function activation_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'openconsent_cmp_activation_notice' ) ) {
			return;
		}

		delete_transient( 'openconsent_cmp_activation_notice' );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'OpenConsent CMP is active.', 'openconsent-cmp' ); ?></strong>
				<?php esc_html_e( 'Review the consent banner, service registry, and Google Consent Mode settings before relying on it in production.', 'openconsent-cmp' ); ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=openconsent-cmp' ) ); ?>"><?php esc_html_e( 'Open settings', 'openconsent-cmp' ); ?></a>
			</p>
		</div>
		<?php
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
			'block_iframes'       => empty( $input['block_iframes'] ) ? 0 : 1,
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
			'google_signal_map_ad_storage' => $this->sanitize_signal_map( $input['google_signal_map_ad_storage'] ?? 'marketing' ),
			'google_signal_map_ad_user_data' => $this->sanitize_signal_map( $input['google_signal_map_ad_user_data'] ?? 'marketing' ),
			'google_signal_map_ad_personalization' => $this->sanitize_signal_map( $input['google_signal_map_ad_personalization'] ?? 'marketing' ),
			'google_signal_map_analytics_storage' => $this->sanitize_signal_map( $input['google_signal_map_analytics_storage'] ?? 'statistics' ),
			'google_signal_map_functionality_storage' => $this->sanitize_signal_map( $input['google_signal_map_functionality_storage'] ?? 'preferences' ),
			'google_signal_map_personalization_storage' => $this->sanitize_signal_map( $input['google_signal_map_personalization_storage'] ?? 'preferences' ),
			'url_passthrough'     => empty( $input['url_passthrough'] ) ? 0 : 1,
			'ads_data_redaction'  => empty( $input['ads_data_redaction'] ) ? 0 : 1,
			'wp_consent_api'      => empty( $input['wp_consent_api'] ) ? 0 : 1,
			'debug_mode'          => empty( $input['debug_mode'] ) ? 0 : 1,
			'scan_page_limit'     => min( 25, max( 1, absint( $input['scan_page_limit'] ?? 5 ) ) ),
			'log_retention_days'  => max( 1, absint( $input['log_retention_days'] ?? 365 ) ),
			'services'            => $this->sanitize_services( $input['services'] ?? '' ),
			'script_handles'      => $this->sanitize_script_handles( $input['script_handles'] ?? '' ),
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
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}

			$pattern     = sanitize_text_field( $parts[0] );
			$category    = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ? $parts[1] : 'unclassified';
			$name        = sanitize_text_field( $parts[2] ?? $pattern );
			$provider    = sanitize_text_field( $parts[3] ?? '' );
			$purpose     = sanitize_textarea_field( $parts[4] ?? '' );
			$privacy_url = esc_url_raw( $parts[5] ?? '' );

			$clean[] = implode( '|', array( $pattern, $category, $name, $provider, $purpose, $privacy_url ) );
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

		$options        = $this->plugin->options();
		$posted_options = isset( $_POST[ OpenConsent_CMP::OPTION ] ) ? (array) wp_unslash( $_POST[ OpenConsent_CMP::OPTION ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $posted_options['scan_page_limit'] ) ) {
			$options['scan_page_limit'] = min( 25, max( 1, absint( $posted_options['scan_page_limit'] ) ) );
		}
		$page_limit = min( 25, max( 1, absint( $options['scan_page_limit'] ?? 5 ) ) );
		$report  = $this->plugin->scanner->scan_site( $page_limit );

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

		$options       = $this->plugin->options();
		$record_filters = $this->record_filters();
		$total_logs    = $this->total_logs();
		$filtered_logs = $this->filtered_logs_count( $record_filters );
		$log_stats     = $this->log_stats();
		$services      = $this->plugin->services();
		$page_count    = max( 1, (int) ceil( $filtered_logs / $record_filters['per_page'] ) );
		$current_page  = min( $record_filters['page'], $page_count );
		$record_filters['page'] = $current_page;
		$logs          = $this->recent_logs( $record_filters );
		$csv_url       = $this->record_export_url( 'openconsent_export_logs', $record_filters );
		$json_url      = $this->record_export_url( 'openconsent_export_logs_json', $record_filters );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'OpenConsent CMP', 'openconsent-cmp' ); ?></h1>
			<p><?php esc_html_e( 'A self-hosted consent manager for WordPress: present clear choices, categorize services, block optional scripts, publish a declaration, record consent choices, and send Google Consent Mode signals.', 'openconsent-cmp' ); ?></p>
			<div class="openconsent-credit-card">
				<div>
					<strong><?php esc_html_e( 'OpenConsent CMP by YASA LTD', 'openconsent-cmp' ); ?></strong>
					<p><?php esc_html_e( 'Open source software is easier to maintain when users can trace the author and support ongoing work.', 'openconsent-cmp' ); ?></p>
				</div>
				<p class="openconsent-credit-actions">
					<a class="button button-secondary" href="<?php echo esc_url( OPENCONSENT_CMP_AUTHOR_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit YASA LTD', 'openconsent-cmp' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( OPENCONSENT_CMP_DONATION_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support open source development', 'openconsent-cmp' ); ?></a>
				</p>
			</div>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Publisher ads note:', 'openconsent-cmp' ); ?></strong> <?php esc_html_e( 'Google requires a Google-certified CMP integrated with the IAB TCF when serving personalized AdSense, Ad Manager, or AdMob ads to users in the EEA, UK, or Switzerland. OpenConsent CMP is self-hosted and is not a Google-certified TCF CMP. Use it for publisher ads only after reviewing your ad mode, regions, and legal requirements.', 'openconsent-cmp' ); ?></p>
			</div>

			<?php if ( isset( $_GET['openconsent_scanned'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Homepage scan completed.', 'openconsent-cmp' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['openconsent_pruned'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action. ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf(
					/* translators: %s: Number of expired consent records removed. */
					__( 'Removed %s expired consent records.', 'openconsent-cmp' ),
					number_format_i18n( absint( $_GET['openconsent_pruned'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action.
				) ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['openconsent_imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'OpenConsent settings imported.', 'openconsent-cmp' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['openconsent_services_imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action. ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf(
					/* translators: %s: Number of imported service registry rows. */
					__( 'Imported %s service registry rows.', 'openconsent-cmp' ),
					number_format_i18n( absint( $_GET['openconsent_services_imported'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only admin notice after a nonced action.
				) ); ?></p></div>
			<?php endif; ?>

			<div class="openconsent-admin-grid" aria-label="<?php esc_attr_e( 'OpenConsent CMP status', 'openconsent-cmp' ); ?>">
				<div class="openconsent-admin-card"><strong><?php echo ! empty( $options['enabled'] ) ? esc_html__( 'On', 'openconsent-cmp' ) : esc_html__( 'Off', 'openconsent-cmp' ); ?></strong><span><?php esc_html_e( 'Frontend banner', 'openconsent-cmp' ); ?></span></div>
				<div class="openconsent-admin-card"><strong><?php echo esc_html( count( $services ) ); ?></strong><span><?php esc_html_e( 'Configured services', 'openconsent-cmp' ); ?></span></div>
				<div class="openconsent-admin-card"><strong><?php echo esc_html( number_format_i18n( $total_logs ) ); ?></strong><span><?php esc_html_e( 'Stored consent records', 'openconsent-cmp' ); ?></span></div>
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
							<p><label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[block_iframes]" value="1" <?php checked( $options['block_iframes'], 1 ); ?>> <?php esc_html_e( 'Block matching iframe embeds in post content until consent', 'openconsent-cmp' ); ?></label></p>
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
								<?php $this->render_signal_mapping_select( 'ad_storage', 'google_signal_map_ad_storage', __( 'Advertising cookies and ad measurement.', 'openconsent-cmp' ), $options ); ?>
								<?php $this->render_signal_mapping_select( 'ad_user_data', 'google_signal_map_ad_user_data', __( 'User data sent to Google for advertising.', 'openconsent-cmp' ), $options ); ?>
								<?php $this->render_signal_mapping_select( 'ad_personalization', 'google_signal_map_ad_personalization', __( 'Personalized ads and remarketing.', 'openconsent-cmp' ), $options ); ?>
								<?php $this->render_signal_mapping_select( 'analytics_storage', 'google_signal_map_analytics_storage', __( 'Analytics cookies and measurement.', 'openconsent-cmp' ), $options ); ?>
								<?php $this->render_signal_mapping_select( 'functionality_storage', 'google_signal_map_functionality_storage', __( 'Functional storage for site features.', 'openconsent-cmp' ), $options ); ?>
								<?php $this->render_signal_mapping_select( 'personalization_storage', 'google_signal_map_personalization_storage', __( 'Personalization storage for saved preferences.', 'openconsent-cmp' ), $options ); ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Each signal is granted only when its mapped category is granted. Choose Always denied to keep a signal denied regardless of visitor choices. OpenConsent always sends security_storage as granted.', 'openconsent-cmp' ); ?></p>
							<p class="description"><a href="https://developers.google.com/tag-platform/security/guides/consent" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Consent Mode setup guide', 'openconsent-cmp' ); ?></a> | <a href="https://www.google.com/about/company/user-consent-policy/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google EU user consent policy', 'openconsent-cmp' ); ?></a></p>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[ads_data_redaction]" value="1" <?php checked( $options['ads_data_redaction'], 1 ); ?>> <?php esc_html_e( 'Enable ads data redaction', 'openconsent-cmp' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[url_passthrough]" value="1" <?php checked( $options['url_passthrough'], 1 ); ?>> <?php esc_html_e( 'Enable URL passthrough', 'openconsent-cmp' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug mode', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[debug_mode]" value="1" <?php checked( $options['debug_mode'], 1 ); ?>> <?php esc_html_e( 'Expose blocked resource diagnostics in the browser console and window.OpenConsentDebug', 'openconsent-cmp' ); ?></label>
							<p class="description"><?php esc_html_e( 'Use only while configuring a site. Debug mode lists blocked scripts and embeds, matched category, provider, purpose, and source URL.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WordPress Consent API', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[wp_consent_api]" value="1" <?php checked( $options['wp_consent_api'], 1 ); ?>> <?php esc_html_e( 'Publish consent choices to the WP Consent API when it is installed', 'openconsent-cmp' ); ?></label>
							<p class="description"><?php esc_html_e( 'OpenConsent declares compatibility to WP Consent API and maps Necessary to functional, Preferences to preferences, Statistics to statistics and statistics-anonymous, and Marketing to marketing. Unclassified services remain blocked until reviewed.', 'openconsent-cmp' ); ?></p>
							<p class="description"><?php esc_html_e( 'This helps compatible plugins, such as analytics or ecommerce integrations, read the same consent state as the banner instead of using separate settings.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-services"><?php esc_html_e( 'Service registry', 'openconsent-cmp' ); ?></label></th>
						<td>
							<textarea id="openconsent-services" class="large-text code" rows="9" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[services]"><?php echo esc_textarea( $options['services'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One service per line: URL pattern | category | display name | provider | purpose | provider privacy URL. Categories: preferences, statistics, marketing, unclassified.', 'openconsent-cmp' ); ?></p>
							<p class="description"><?php esc_html_e( 'Older three-field lines still work and are normalized when settings are saved.', 'openconsent-cmp' ); ?></p>
							<p class="description"><?php esc_html_e( 'Examples:', 'openconsent-cmp' ); ?> <code>analytics.example.com|statistics|Analytics tool|Example Ltd|Audience measurement.|https://example.com/privacy</code> <code>unknown.example.com|unclassified|Review needed|||</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-script-handles"><?php esc_html_e( 'WordPress script handles', 'openconsent-cmp' ); ?></label></th>
						<td>
							<textarea id="openconsent-script-handles" class="large-text code" rows="5" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[script_handles]"><?php echo esc_textarea( $options['script_handles'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional. One registered script per line: WordPress handle | category | display name. This is useful when a plugin registers a script from a local URL or a URL pattern is not enough.', 'openconsent-cmp' ); ?></p>
							<p class="description"><?php esc_html_e( 'Example:', 'openconsent-cmp' ); ?> <code>contact-form-analytics|statistics|Contact form analytics</code></p>
						</td>
					</tr>
				</table>
				</div>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Service inventory', 'openconsent-cmp' ); ?></h2>
				<p><?php esc_html_e( 'Review configured services in a structured table. Edit the textarea above when you need to change the registry.', 'openconsent-cmp' ); ?></p>
				<?php $this->render_service_inventory( $services ); ?>
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
				<h2><?php esc_html_e( 'Configuration import and export', 'openconsent-cmp' ); ?></h2>
				<p><?php esc_html_e( 'Export settings before major changes, or move a registry between WordPress installations.', 'openconsent-cmp' ); ?></p>
				<p class="openconsent-export-actions">
					<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=openconsent_export_settings' ), 'openconsent_export_settings' ) ); ?>"><?php esc_html_e( 'Export settings JSON', 'openconsent-cmp' ); ?></a>
					<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=openconsent_export_services' ), 'openconsent_export_services' ) ); ?>"><?php esc_html_e( 'Export services CSV', 'openconsent-cmp' ); ?></a>
				</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="openconsent_import_settings">
					<?php wp_nonce_field( 'openconsent_import_settings' ); ?>
					<label for="openconsent-import-file"><?php esc_html_e( 'Import settings JSON', 'openconsent-cmp' ); ?></label>
					<input id="openconsent-import-file" type="file" name="openconsent_settings_file" accept="application/json,.json">
					<?php submit_button( __( 'Import settings', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
				</form>
				<p class="description"><?php esc_html_e( 'Import replaces plugin settings but keeps existing consent records.', 'openconsent-cmp' ); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="openconsent-import-services-form">
					<input type="hidden" name="action" value="openconsent_import_services">
					<?php wp_nonce_field( 'openconsent_import_services' ); ?>
					<label for="openconsent-import-services-file"><?php esc_html_e( 'Import services CSV', 'openconsent-cmp' ); ?></label>
					<input id="openconsent-import-services-file" type="file" name="openconsent_services_file" accept="text/csv,.csv">
					<label><input type="radio" name="openconsent_services_mode" value="replace" checked> <?php esc_html_e( 'Replace current service registry', 'openconsent-cmp' ); ?></label>
					<label><input type="radio" name="openconsent_services_mode" value="append"> <?php esc_html_e( 'Append to current service registry', 'openconsent-cmp' ); ?></label>
					<?php submit_button( __( 'Import services', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
				</form>
				<p class="description"><?php esc_html_e( 'CSV columns: pattern, category, name, provider, purpose, privacy_url. Header rows are accepted.', 'openconsent-cmp' ); ?></p>
				</div>

				<div class="openconsent-settings-card">
				<h2><?php esc_html_e( 'Audit log', 'openconsent-cmp' ); ?></h2>
				<p><?php esc_html_e( 'Consent choices are stored in a local WordPress database table with anonymized visitor hashes. Use exports for audits, support requests, or compliance reviews.', 'openconsent-cmp' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="openconsent-retention"><?php esc_html_e( 'Retention', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-retention" type="number" min="1" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[log_retention_days]" value="<?php echo esc_attr( $options['log_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'openconsent-cmp' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stored records', 'openconsent-cmp' ); ?></th>
						<td>
							<strong><?php echo esc_html( number_format_i18n( $total_logs ) ); ?></strong>
							<?php esc_html_e( 'records currently stored.', 'openconsent-cmp' ); ?>
							<div class="openconsent-export-actions">
								<a class="button button-secondary" href="<?php echo esc_url( $csv_url ); ?>"><?php esc_html_e( 'Download CSV', 'openconsent-cmp' ); ?></a>
								<a class="button button-secondary" href="<?php echo esc_url( $json_url ); ?>"><?php esc_html_e( 'Download JSON', 'openconsent-cmp' ); ?></a>
							</div>
							<p class="description"><?php esc_html_e( 'Exports include consent ID, timestamp, selected categories, action, region, language, page URL, consent hash, IP hash, and user-agent hash.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cleanup', 'openconsent-cmp' ); ?></th>
						<td>
							<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=openconsent_prune_logs' ), 'openconsent_prune_logs' ) ); ?>"><?php esc_html_e( 'Prune expired records now', 'openconsent-cmp' ); ?></a>
							<p class="description"><?php esc_html_e( 'Deletes records older than the retention period above. Current filters do not affect cleanup.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
				</table>
				</div>

				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Crawl scan', 'openconsent-cmp' ); ?></h2>
			<p><?php esc_html_e( 'The scanner crawls internal pages, records Set-Cookie headers, finds static external script/embed/media hosts, and suggests unreviewed service registry entries. It does not execute JavaScript like a full browser crawler.', 'openconsent-cmp' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="openconsent_run_scan">
				<?php wp_nonce_field( 'openconsent_run_scan' ); ?>
				<label for="openconsent-scan-limit"><?php esc_html_e( 'Pages to scan', 'openconsent-cmp' ); ?></label>
				<input id="openconsent-scan-limit" type="number" min="1" max="25" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[scan_page_limit]" value="<?php echo esc_attr( $options['scan_page_limit'] ); ?>">
				<?php submit_button( __( 'Run crawl scan', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php $this->render_scan_report( $options ); ?>

			<h2><?php esc_html_e( 'Consent records', 'openconsent-cmp' ); ?></h2>
			<div class="openconsent-record-summary" aria-label="<?php esc_attr_e( 'Consent record summary', 'openconsent-cmp' ); ?>">
				<span><strong><?php echo esc_html( number_format_i18n( $total_logs ) ); ?></strong> <?php esc_html_e( 'total', 'openconsent-cmp' ); ?></span>
				<span><strong><?php echo esc_html( number_format_i18n( $log_stats['accept_all'] ) ); ?></strong> <?php esc_html_e( 'accepted all', 'openconsent-cmp' ); ?></span>
				<span><strong><?php echo esc_html( number_format_i18n( $log_stats['necessary_only'] ) ); ?></strong> <?php esc_html_e( 'necessary only', 'openconsent-cmp' ); ?></span>
				<span><strong><?php echo esc_html( number_format_i18n( $log_stats['save_choices'] ) ); ?></strong> <?php esc_html_e( 'custom choices', 'openconsent-cmp' ); ?></span>
			</div>
			<p>
				<a class="button button-secondary" href="<?php echo esc_url( $csv_url ); ?>"><?php esc_html_e( 'Download current view as CSV', 'openconsent-cmp' ); ?></a>
				<a class="button button-secondary" href="<?php echo esc_url( $json_url ); ?>"><?php esc_html_e( 'Download current view as JSON', 'openconsent-cmp' ); ?></a>
			</p>
			<form class="openconsent-record-filters" method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
				<input type="hidden" name="page" value="openconsent-cmp">
				<label>
					<span><?php esc_html_e( 'Action', 'openconsent-cmp' ); ?></span>
					<select name="openconsent_action">
						<option value=""><?php esc_html_e( 'All actions', 'openconsent-cmp' ); ?></option>
						<option value="accept_all" <?php selected( $record_filters['action'], 'accept_all' ); ?>><?php esc_html_e( 'Accept all', 'openconsent-cmp' ); ?></option>
						<option value="necessary_only" <?php selected( $record_filters['action'], 'necessary_only' ); ?>><?php esc_html_e( 'Necessary only', 'openconsent-cmp' ); ?></option>
						<option value="save_choices" <?php selected( $record_filters['action'], 'save_choices' ); ?>><?php esc_html_e( 'Saved choices', 'openconsent-cmp' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Granted category', 'openconsent-cmp' ); ?></span>
					<select name="openconsent_category">
						<option value=""><?php esc_html_e( 'Any category', 'openconsent-cmp' ); ?></option>
						<option value="preferences" <?php selected( $record_filters['category'], 'preferences' ); ?>><?php esc_html_e( 'Preferences', 'openconsent-cmp' ); ?></option>
						<option value="statistics" <?php selected( $record_filters['category'], 'statistics' ); ?>><?php esc_html_e( 'Statistics', 'openconsent-cmp' ); ?></option>
						<option value="marketing" <?php selected( $record_filters['category'], 'marketing' ); ?>><?php esc_html_e( 'Marketing', 'openconsent-cmp' ); ?></option>
						<option value="unclassified" <?php selected( $record_filters['category'], 'unclassified' ); ?>><?php esc_html_e( 'Unclassified', 'openconsent-cmp' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'From', 'openconsent-cmp' ); ?></span>
					<input type="date" name="openconsent_from" value="<?php echo esc_attr( $record_filters['from'] ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'To', 'openconsent-cmp' ); ?></span>
					<input type="date" name="openconsent_to" value="<?php echo esc_attr( $record_filters['to'] ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Search', 'openconsent-cmp' ); ?></span>
					<input type="search" name="openconsent_search" value="<?php echo esc_attr( $record_filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Consent ID or URL', 'openconsent-cmp' ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Per page', 'openconsent-cmp' ); ?></span>
					<select name="openconsent_per_page">
						<?php foreach ( array( 25, 50, 100 ) as $per_page ) : ?>
							<option value="<?php echo esc_attr( $per_page ); ?>" <?php selected( $record_filters['per_page'], $per_page ); ?>><?php echo esc_html( $per_page ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php submit_button( __( 'Filter records', 'openconsent-cmp' ), 'secondary', 'submit', false ); ?>
				<a class="button button-link" href="<?php echo esc_url( admin_url( 'options-general.php?page=openconsent-cmp' ) ); ?>"><?php esc_html_e( 'Reset', 'openconsent-cmp' ); ?></a>
			</form>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Consent ID', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Action', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Categories', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Region', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Language', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Page', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Hash', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Details', 'openconsent-cmp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="9"><?php esc_html_e( 'No consent records match the current filters.', 'openconsent-cmp' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->created_at ); ?></td>
								<td><code><?php echo esc_html( $log->consent_id ); ?></code></td>
								<td><?php echo esc_html( $this->format_action_label( $log->consent_action ) ); ?></td>
								<td><?php echo wp_kses_post( $this->format_category_badges( $log ) ); ?></td>
								<td><?php echo esc_html( trim( $log->region . ' / ' . $log->region_mode, ' /' ) ); ?></td>
								<td><?php echo esc_html( $log->language ); ?></td>
								<td><?php echo $log->page_url ? '<a href="' . esc_url( $log->page_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( wp_parse_url( $log->page_url, PHP_URL_PATH ) ?: $log->page_url ) . '</a>' : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								<td><code><?php echo esc_html( substr( $log->consent_hash, 0, 12 ) ); ?></code></td>
								<td><?php $this->render_record_details( $log ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<div class="openconsent-pagination">
				<span><?php echo esc_html( sprintf(
					/* translators: 1: Number of records shown, 2: Total number of matching records. */
					__( 'Showing %1$s of %2$s matching records.', 'openconsent-cmp' ),
					number_format_i18n( count( $logs ) ),
					number_format_i18n( $filtered_logs )
				) ); ?></span>
				<?php if ( $current_page > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( $this->record_page_url( $record_filters, $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'openconsent-cmp' ); ?></a>
				<?php endif; ?>
				<span><?php echo esc_html( sprintf(
					/* translators: 1: Current page number, 2: Total number of pages. */
					__( 'Page %1$s of %2$s', 'openconsent-cmp' ),
					number_format_i18n( $current_page ),
					number_format_i18n( $page_count )
				) ); ?></span>
				<?php if ( $current_page < $page_count ) : ?>
					<a class="button" href="<?php echo esc_url( $this->record_page_url( $record_filters, $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'openconsent-cmp' ); ?></a>
				<?php endif; ?>
			</div>

			<p><?php esc_html_e( 'Use shortcode [openconsent_declaration] on a Cookie Policy page to publish the declaration.', 'openconsent-cmp' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render configured services as an inventory table.
	 *
	 * @param array $services Parsed service registry rows.
	 * @return void
	 */
	private function render_service_inventory( $services ) {
		if ( empty( $services ) ) {
			?>
			<p><?php esc_html_e( 'No services are configured yet. Add one service rule per line in the service registry.', 'openconsent-cmp' ); ?></p>
			<?php
			return;
		}
		?>
		<table class="widefat striped openconsent-inventory-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Service', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Category', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Provider', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Purpose', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'URL rule', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Privacy policy', 'openconsent-cmp' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Review status', 'openconsent-cmp' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $services as $service ) : ?>
					<?php
					$complete = ! empty( $service['provider'] ) && ! empty( $service['purpose'] ) && ! empty( $service['privacy_url'] );
					?>
					<tr>
						<td><strong><?php echo esc_html( $service['name'] ?? $service['pattern'] ); ?></strong></td>
						<td><span class="openconsent-badge openconsent-badge--category"><?php echo esc_html( ucfirst( $service['category'] ?? 'unclassified' ) ); ?></span></td>
						<td><?php echo esc_html( $service['provider'] ?? '' ); ?></td>
						<td><?php echo esc_html( $service['purpose'] ?? '' ); ?></td>
						<td><code><?php echo esc_html( $service['pattern'] ?? '' ); ?></code></td>
						<td>
							<?php if ( ! empty( $service['privacy_url'] ) ) : ?>
								<a href="<?php echo esc_url( $service['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open', 'openconsent-cmp' ); ?></a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
						<td>
							<span class="openconsent-badge openconsent-badge--<?php echo $complete ? 'granted' : 'denied'; ?>">
								<?php echo esc_html( $complete ? __( 'Documented', 'openconsent-cmp' ) : __( 'Needs details', 'openconsent-cmp' ) ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render latest scanner report.
	 *
	 * @param array $options Plugin options.
	 * @return void
	 */
	private function render_scan_report( $options ) {
		$report = isset( $options['scan_report'] ) && is_array( $options['scan_report'] ) ? $options['scan_report'] : array();

		if ( empty( $report ) ) {
			?>
			<p><?php esc_html_e( 'No crawl report has been generated yet.', 'openconsent-cmp' ); ?></p>
			<?php
			return;
		}

		if ( empty( $report['version'] ) || 2 > absint( $report['version'] ) ) {
			?>
			<ul class="openconsent-help-list">
				<?php foreach ( $report as $finding ) : ?>
					<li><?php echo esc_html( $finding ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
			return;
		}

		$summary = wp_parse_args(
			$report['summary'] ?? array(),
			array(
				'pages_scanned'      => 0,
				'cookies_found'      => 0,
				'external_resources' => 0,
				'suggestions'        => 0,
			)
		);
		?>
		<div class="openconsent-record-summary" aria-label="<?php esc_attr_e( 'Crawl report summary', 'openconsent-cmp' ); ?>">
			<div><strong><?php echo esc_html( number_format_i18n( $summary['pages_scanned'] ) ); ?></strong><span><?php esc_html_e( 'Pages scanned', 'openconsent-cmp' ); ?></span></div>
			<div><strong><?php echo esc_html( number_format_i18n( $summary['cookies_found'] ) ); ?></strong><span><?php esc_html_e( 'Set-Cookie headers', 'openconsent-cmp' ); ?></span></div>
			<div><strong><?php echo esc_html( number_format_i18n( $summary['external_resources'] ) ); ?></strong><span><?php esc_html_e( 'External resources', 'openconsent-cmp' ); ?></span></div>
			<div><strong><?php echo esc_html( number_format_i18n( $summary['suggestions'] ) ); ?></strong><span><?php esc_html_e( 'Registry suggestions', 'openconsent-cmp' ); ?></span></div>
		</div>

		<h3><?php esc_html_e( 'Scanned pages', 'openconsent-cmp' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'URL', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Cookies', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Resources', 'openconsent-cmp' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $report['pages'] ?? array() as $page ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( $page['url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $page['url'] ?? '' ); ?></a></td>
						<td><?php echo esc_html( $page['message'] ?? $page['status'] ?? '' ); ?></td>
						<td><?php echo esc_html( number_format_i18n( count( $page['cookies'] ?? array() ) ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( count( $page['resources'] ?? array() ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Cookies found', 'openconsent-cmp' ); ?></h3>
		<?php $this->render_scan_cookies_table( $report['cookies'] ?? array() ); ?>

		<h3><?php esc_html_e( 'External resources', 'openconsent-cmp' ); ?></h3>
		<?php $this->render_scan_resources_table( $report['resources'] ?? array() ); ?>

		<h3><?php esc_html_e( 'Suggested service rows', 'openconsent-cmp' ); ?></h3>
		<?php $this->render_scan_suggestions( $report['suggestions'] ?? array() ); ?>
		<?php
	}

	/**
	 * Render cookies from scanner report.
	 *
	 * @param array $cookies Cookie rows.
	 * @return void
	 */
	private function render_scan_cookies_table( $cookies ) {
		if ( empty( $cookies ) ) {
			echo '<p>' . esc_html__( 'No Set-Cookie headers were found in the scanned responses.', 'openconsent-cmp' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'Cookie name', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Expiry hint', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Page', 'openconsent-cmp' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $cookies as $cookie ) : ?>
					<tr>
						<td><code><?php echo esc_html( $cookie['name'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( $cookie['expires'] ?? '' ); ?></td>
						<td><a href="<?php echo esc_url( $cookie['page_url'] ?? '' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $cookie['page_url'] ?? '' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render external resources from scanner report.
	 *
	 * @param array $resources Resource rows.
	 * @return void
	 */
	private function render_scan_resources_table( $resources ) {
		if ( empty( $resources ) ) {
			echo '<p>' . esc_html__( 'No external static resources were found in scanned markup.', 'openconsent-cmp' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'Host', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Type', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Suggested category', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'Registry match', 'openconsent-cmp' ); ?></th><th scope="col"><?php esc_html_e( 'URL', 'openconsent-cmp' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $resources as $resource ) : ?>
					<tr>
						<td><?php echo esc_html( $resource['host'] ?? '' ); ?></td>
						<td><?php echo esc_html( $resource['type'] ?? '' ); ?></td>
						<td><?php echo esc_html( ucfirst( $resource['category'] ?? 'unclassified' ) ); ?></td>
						<td><?php echo ! empty( $resource['matched'] ) ? esc_html__( 'Configured', 'openconsent-cmp' ) : esc_html__( 'Needs review', 'openconsent-cmp' ); ?></td>
						<td><code><?php echo esc_html( $resource['url'] ?? '' ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render suggested service registry rows from scanner report.
	 *
	 * @param array $suggestions Suggestion rows.
	 * @return void
	 */
	private function render_scan_suggestions( $suggestions ) {
		if ( empty( $suggestions ) ) {
			echo '<p>' . esc_html__( 'No unconfigured external resources were found.', 'openconsent-cmp' ) . '</p>';
			return;
		}
		$rows = array();
		foreach ( $suggestions as $suggestion ) {
			$rows[] = implode(
				'|',
				array(
					$suggestion['pattern'] ?? '',
					$suggestion['category'] ?? 'unclassified',
					$suggestion['name'] ?? '',
					$suggestion['provider'] ?? '',
					$suggestion['purpose'] ?? '',
					$suggestion['privacy_url'] ?? '',
				)
			);
		}
		?>
		<p><?php esc_html_e( 'Review these rows before adding them to the service registry. The category is a heuristic suggestion, not a legal classification.', 'openconsent-cmp' ); ?></p>
		<textarea class="large-text code" rows="5" readonly><?php echo esc_textarea( implode( "\n", $rows ) ); ?></textarea>
		<?php
	}

	/**
	 * Get recent consent logs.
	 *
	 * @param array $filters Record filters.
	 * @return array
	 */
	private function recent_logs( $filters = array() ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array();
		}

		$filters = wp_parse_args( $filters, $this->record_filters( array() ) );
		$params  = array();
		$where   = $this->record_where_sql( $filters, $params );
		$limit   = max( 1, absint( $filters['per_page'] ) );
		$offset  = max( 0, ( absint( $filters['page'] ) - 1 ) * $limit );
		$params[] = $limit;
		$params[] = $offset;

		$sql = "SELECT created_at, consent_id, consent_action, necessary, preferences, statistics, marketing, unclassified, region, region_mode, language, page_url, referrer_url, plugin_version, consent_hash, ip_hash, user_agent_hash, consent_json FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name and WHERE fragments are built from whitelisted values above.
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Get total stored consent logs.
	 *
	 * @return int
	 */
	private function total_logs() {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from the WordPress prefix and a plugin constant.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Count consent logs matching the current admin filters.
	 *
	 * @param array $filters Record filters.
	 * @return int
	 */
	private function filtered_logs_count( $filters ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
		}

		$params = array();
		$where  = $this->record_where_sql( $filters, $params );
		$sql    = "SELECT COUNT(*) FROM {$table} WHERE {$where}";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name and WHERE fragments are built from whitelisted values above.
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name and WHERE fragments are built from whitelisted values above.
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Build sanitized record filters from request data.
	 *
	 * @param array|null $source Request source. Defaults to $_GET.
	 * @return array
	 */
	private function record_filters( $source = null ) {
		$source = is_array( $source ) ? $source : $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $source['openconsent_action'] ?? '' ) );
		$category = sanitize_key( wp_unslash( $source['openconsent_category'] ?? '' ) );
		$from = sanitize_text_field( wp_unslash( $source['openconsent_from'] ?? '' ) );
		$to = sanitize_text_field( wp_unslash( $source['openconsent_to'] ?? '' ) );
		$per_page = absint( $source['openconsent_per_page'] ?? 50 );

		if ( ! in_array( $action, array( 'accept_all', 'necessary_only', 'save_choices' ), true ) ) {
			$action = '';
		}

		if ( ! in_array( $category, array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ) {
			$category = '';
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
			$from = '';
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$to = '';
		}

		if ( ! in_array( $per_page, array( 25, 50, 100 ), true ) ) {
			$per_page = 50;
		}

		return array(
			'action'   => $action,
			'category' => $category,
			'from'     => $from,
			'to'       => $to,
			'search'   => sanitize_text_field( wp_unslash( $source['openconsent_search'] ?? '' ) ),
			'page'     => max( 1, absint( $source['openconsent_page'] ?? 1 ) ),
			'per_page' => $per_page,
		);
	}

	/**
	 * Create a whitelisted SQL WHERE clause and parameter list.
	 *
	 * @param array $filters Record filters.
	 * @param array $params  Query params passed by reference.
	 * @return string
	 */
	private function record_where_sql( $filters, &$params ) {
		global $wpdb;
		$where = array( '1=1' );
		$params = array();

		if ( ! empty( $filters['action'] ) ) {
			$where[] = 'consent_action = %s';
			$params[] = $filters['action'];
		}

		if ( ! empty( $filters['category'] ) && in_array( $filters['category'], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ) {
			$where[] = "`{$filters['category']}` = 1";
		}

		if ( ! empty( $filters['from'] ) ) {
			$where[] = 'created_at >= %s';
			$params[] = $filters['from'] . ' 00:00:00';
		}

		if ( ! empty( $filters['to'] ) ) {
			$where[] = 'created_at <= %s';
			$params[] = $filters['to'] . ' 23:59:59';
		}

		if ( ! empty( $filters['search'] ) ) {
			$like = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where[] = '(consent_id LIKE %s OR page_url LIKE %s OR referrer_url LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		return implode( ' AND ', $where );
	}

	/**
	 * Build export URL that preserves current record filters.
	 *
	 * @param string $action  Admin-post action.
	 * @param array  $filters Record filters.
	 * @return string
	 */
	private function record_export_url( $action, $filters ) {
		$args = array(
			'action' => $action,
		);

		foreach ( $this->filter_query_args( $filters ) as $key => $value ) {
			$args[ $key ] = $value;
		}

		return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'openconsent_export_logs' );
	}

	/**
	 * Build a pagination URL for the records table.
	 *
	 * @param array $filters Record filters.
	 * @param int   $page    Page number.
	 * @return string
	 */
	private function record_page_url( $filters, $page ) {
		$args = array_merge(
			array(
				'page' => 'openconsent-cmp',
				'openconsent_page' => max( 1, absint( $page ) ),
			),
			$this->filter_query_args( $filters )
		);

		return add_query_arg( $args, admin_url( 'options-general.php' ) );
	}

	/**
	 * Return active filter query args.
	 *
	 * @param array $filters Record filters.
	 * @return array
	 */
	private function filter_query_args( $filters ) {
		$args = array();
		$map  = array(
			'action'   => 'openconsent_action',
			'category' => 'openconsent_category',
			'from'     => 'openconsent_from',
			'to'       => 'openconsent_to',
			'search'   => 'openconsent_search',
			'per_page' => 'openconsent_per_page',
		);

		foreach ( $map as $filter_key => $query_key ) {
			if ( isset( $filters[ $filter_key ] ) && '' !== (string) $filters[ $filter_key ] ) {
				$args[ $query_key ] = $filters[ $filter_key ];
			}
		}

		return $args;
	}

	/**
	 * Render expandable consent record detail.
	 *
	 * @param object $log Consent log row.
	 * @return void
	 */
	private function render_record_details( $log ) {
		?>
		<details class="openconsent-record-detail">
			<summary><?php esc_html_e( 'View', 'openconsent-cmp' ); ?></summary>
			<dl>
				<dt><?php esc_html_e( 'Referrer', 'openconsent-cmp' ); ?></dt>
				<dd><?php echo $log->referrer_url ? '<a href="' . esc_url( $log->referrer_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( wp_parse_url( $log->referrer_url, PHP_URL_PATH ) ?: $log->referrer_url ) . '</a>' : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
				<dt><?php esc_html_e( 'Plugin version', 'openconsent-cmp' ); ?></dt>
				<dd><code><?php echo esc_html( $log->plugin_version ); ?></code></dd>
				<dt><?php esc_html_e( 'IP hash', 'openconsent-cmp' ); ?></dt>
				<dd><code><?php echo esc_html( substr( (string) $log->ip_hash, 0, 16 ) ); ?></code></dd>
				<dt><?php esc_html_e( 'User-agent hash', 'openconsent-cmp' ); ?></dt>
				<dd><code><?php echo esc_html( substr( (string) $log->user_agent_hash, 0, 16 ) ); ?></code></dd>
				<dt><?php esc_html_e( 'Raw consent JSON', 'openconsent-cmp' ); ?></dt>
				<dd><textarea class="large-text code" rows="4" readonly><?php echo esc_textarea( $log->consent_json ); ?></textarea></dd>
			</dl>
		</details>
		<?php
	}

	/**
	 * Get basic consent action counts.
	 *
	 * @return array
	 */
	private function log_stats() {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );
		$stats = array(
			'accept_all'     => 0,
			'necessary_only' => 0,
			'save_choices'   => 0,
		);

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return $stats;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from the WordPress prefix and a plugin constant.
		$rows = $wpdb->get_results( "SELECT consent_action, COUNT(*) AS total FROM {$table} GROUP BY consent_action", ARRAY_A );
		foreach ( $rows as $row ) {
			$action = isset( $row['consent_action'] ) && '' !== $row['consent_action'] ? $row['consent_action'] : 'save_choices';
			if ( isset( $stats[ $action ] ) ) {
				$stats[ $action ] = (int) $row['total'];
			}
		}

		return $stats;
	}

	/**
	 * Convert action key to an admin label.
	 *
	 * @param string $action Action key.
	 * @return string
	 */
	private function format_action_label( $action ) {
		$labels = array(
			'accept_all'     => __( 'Accept all', 'openconsent-cmp' ),
			'necessary_only' => __( 'Necessary only', 'openconsent-cmp' ),
			'save_choices'   => __( 'Saved choices', 'openconsent-cmp' ),
		);

		return $labels[ $action ] ?? ( $action ? ucwords( str_replace( '_', ' ', $action ) ) : __( 'Saved choices', 'openconsent-cmp' ) );
	}

	/**
	 * Render compact category badges for a consent record.
	 *
	 * @param object $log Consent log row.
	 * @return string
	 */
	private function format_category_badges( $log ) {
		$labels = array(
			'necessary'    => __( 'Necessary', 'openconsent-cmp' ),
			'preferences'  => __( 'Preferences', 'openconsent-cmp' ),
			'statistics'   => __( 'Statistics', 'openconsent-cmp' ),
			'marketing'    => __( 'Marketing', 'openconsent-cmp' ),
			'unclassified' => __( 'Unclassified', 'openconsent-cmp' ),
		);
		$output = array();

		foreach ( $labels as $key => $label ) {
			$granted = ! empty( $log->{$key} );
			$output[] = sprintf(
				'<span class="openconsent-badge openconsent-badge--%1$s">%2$s: %3$s</span>',
				$granted ? 'granted' : 'denied',
				esc_html( $label ),
				esc_html( $granted ? __( 'yes', 'openconsent-cmp' ) : __( 'no', 'openconsent-cmp' ) )
			);
		}

		return implode( ' ', $output );
	}

	/**
	 * Sanitize WordPress script handle registry.
	 *
	 * @param string $handles Raw handle lines.
	 * @return string
	 */
	private function sanitize_script_handles( $handles ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $handles );
		$clean = array();

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', sanitize_text_field( $line ) ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}

			$handle   = sanitize_key( $parts[0] );
			$category = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ? $parts[1] : 'unclassified';
			$name     = isset( $parts[2] ) ? $parts[2] : $handle;
			if ( '' !== $handle ) {
				$clean[] = "{$handle}|{$category}|{$name}";
			}
		}

		return implode( "\n", $clean );
	}

	/**
	 * Sanitize Google consent signal mapping.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function sanitize_signal_map( $value ) {
		return in_array( $value, array( 'preferences', 'statistics', 'marketing', 'unclassified', 'denied' ), true ) ? $value : 'denied';
	}

	/**
	 * Delete consent records older than the configured retention period.
	 *
	 * @return void
	 */
	public function prune_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to prune consent logs.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_prune_logs' );

		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=openconsent-cmp&openconsent_pruned=0' ) );
			exit;
		}

		$options = $this->plugin->options();
		$days    = max( 1, absint( $options['log_retention_days'] ?? 365 ) );
		$cutoff  = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from the WordPress prefix and a plugin constant.
		$deleted = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) );

		wp_safe_redirect( admin_url( 'options-general.php?page=openconsent-cmp&openconsent_pruned=' . max( 0, $deleted ) ) );
		exit;
	}

	/**
	 * Render a Google consent signal category mapping control.
	 *
	 * @param string $signal      Google signal name.
	 * @param string $option_key  Option key.
	 * @param string $description Help text.
	 * @param array  $options     Plugin options.
	 * @return void
	 */
	private function render_signal_mapping_select( $signal, $option_key, $description, $options ) {
		$choices = array(
			'marketing'    => __( 'Marketing grants this signal', 'openconsent-cmp' ),
			'statistics'   => __( 'Statistics grants this signal', 'openconsent-cmp' ),
			'preferences'  => __( 'Preferences grants this signal', 'openconsent-cmp' ),
			'unclassified' => __( 'Unclassified grants this signal', 'openconsent-cmp' ),
			'denied'       => __( 'Always denied', 'openconsent-cmp' ),
		);
		?>
		<label class="openconsent-signal-map">
			<code><?php echo esc_html( $signal ); ?></code>
			<select name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[<?php echo esc_attr( $option_key ); ?>]">
				<?php foreach ( $choices as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options[ $option_key ], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php echo esc_html( $description ); ?></span>
		</label>
		<?php
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

		$rows = $this->export_rows( $this->record_filters() );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="openconsent-cmp-logs-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array_keys( $this->export_header_map() ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, $this->normalize_export_row( $row ) );
		}
		exit;
	}

	/**
	 * Export consent logs as JSON for admin download.
	 *
	 * @return void
	 */
	public function export_logs_json() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export consent logs.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_export_logs' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="openconsent-cmp-logs-' . gmdate( 'Y-m-d' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode(
			array(
				'generated_at' => gmdate( 'c' ),
				'site_url'     => home_url( '/' ),
				'plugin'       => 'OpenConsent CMP',
				'version'      => OPENCONSENT_CMP_VERSION,
				'records'      => array_map( array( $this, 'normalize_export_row' ), $this->export_rows( $this->record_filters() ) ),
			),
			JSON_PRETTY_PRINT
		);
		exit;
	}

	/**
	 * Export plugin settings as JSON.
	 *
	 * @return void
	 */
	public function export_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export settings.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_export_settings' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="openconsent-cmp-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode(
			array(
				'plugin'       => 'OpenConsent CMP',
				'version'      => OPENCONSENT_CMP_VERSION,
				'generated_at' => gmdate( 'c' ),
				'site_url'     => home_url( '/' ),
				'settings'     => $this->plugin->options(),
			),
			JSON_PRETTY_PRINT
		);
		exit;
	}

	/**
	 * Export service registry as CSV.
	 *
	 * @return void
	 */
	public function export_services() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export services.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_export_services' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="openconsent-cmp-services-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'pattern', 'category', 'name', 'provider', 'purpose', 'privacy_url' ) );
		foreach ( $this->plugin->services() as $service ) {
			fputcsv(
				$output,
				array(
					$service['pattern'] ?? '',
					$service['category'] ?? 'unclassified',
					$service['name'] ?? '',
					$service['provider'] ?? '',
					$service['purpose'] ?? '',
					$service['privacy_url'] ?? '',
				)
			);
		}
		exit;
	}

	/**
	 * Import plugin settings from a JSON export.
	 *
	 * @return void
	 */
	public function import_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import settings.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_import_settings' );

		$settings_tmp_name = isset( $_FILES['openconsent_settings_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['openconsent_settings_file']['tmp_name'] ) ) : '';
		if ( '' === $settings_tmp_name || ! is_uploaded_file( $settings_tmp_name ) ) {
			wp_die( esc_html__( 'No settings file was uploaded.', 'openconsent-cmp' ) );
		}

		$raw = file_get_contents( $settings_tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === trim( $raw ) ) {
			wp_die( esc_html__( 'The uploaded settings file is empty.', 'openconsent-cmp' ) );
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			wp_die( esc_html__( 'The uploaded settings file is not valid JSON.', 'openconsent-cmp' ) );
		}

		$settings = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : $decoded;
		update_option( OpenConsent_CMP::OPTION, $this->sanitize_options( $settings ) );

		wp_safe_redirect( admin_url( 'options-general.php?page=openconsent-cmp&openconsent_imported=1' ) );
		exit;
	}

	/**
	 * Import service registry rows from CSV.
	 *
	 * @return void
	 */
	public function import_services() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import services.', 'openconsent-cmp' ) );
		}

		check_admin_referer( 'openconsent_import_services' );

		$services_tmp_name = isset( $_FILES['openconsent_services_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['openconsent_services_file']['tmp_name'] ) ) : '';
		if ( '' === $services_tmp_name || ! is_uploaded_file( $services_tmp_name ) ) {
			wp_die( esc_html__( 'No services CSV file was uploaded.', 'openconsent-cmp' ) );
		}

		$csv_contents = file_get_contents( $services_tmp_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $csv_contents || '' === trim( $csv_contents ) ) {
			wp_die( esc_html__( 'The services CSV file could not be opened.', 'openconsent-cmp' ) );
		}

		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $csv_contents ) as $csv_line ) {
			if ( '' === trim( $csv_line ) ) {
				continue;
			}
			$row  = str_getcsv( $csv_line );
			$line = $this->service_line_from_csv_row( $row );
			if ( '' !== $line ) {
				$rows[] = $line;
			}
		}

		if ( empty( $rows ) ) {
			wp_die( esc_html__( 'The services CSV did not contain valid service rows.', 'openconsent-cmp' ) );
		}

		$options       = $this->plugin->options();
		$mode          = isset( $_POST['openconsent_services_mode'] ) && 'append' === sanitize_key( wp_unslash( $_POST['openconsent_services_mode'] ) ) ? 'append' : 'replace';
		$current_rows  = 'append' === $mode ? preg_split( '/\r\n|\r|\n/', (string) $options['services'] ) : array();
		$merged        = array_merge( array_filter( array_map( 'trim', $current_rows ) ), $rows );
		$options['services'] = $this->sanitize_services( implode( "\n", array_unique( $merged ) ) );
		update_option( OpenConsent_CMP::OPTION, $options );

		wp_safe_redirect( admin_url( 'options-general.php?page=openconsent-cmp&openconsent_services_imported=' . count( $rows ) ) );
		exit;
	}

	/**
	 * Convert a CSV row to a service registry line.
	 *
	 * @param array $row Raw CSV row.
	 * @return string
	 */
	private function service_line_from_csv_row( $row ) {
		$row = array_map( 'trim', array_map( 'strval', $row ) );
		if ( empty( $row ) || '' === implode( '', $row ) ) {
			return '';
		}

		$first = strtolower( $row[0] ?? '' );
		if ( in_array( $first, array( 'pattern', 'url pattern', 'match pattern' ), true ) ) {
			return '';
		}

		$pattern = $row[0] ?? '';
		$category = $row[1] ?? 'unclassified';
		if ( '' === $pattern || ! in_array( $category, array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ) {
			return '';
		}

		return implode(
			'|',
			array(
				$pattern,
				$category,
				$row[2] ?? $pattern,
				$row[3] ?? '',
				$row[4] ?? '',
				$row[5] ?? '',
			)
		);
	}

	/**
	 * Register WordPress dashboard widget with consent record shortcuts.
	 *
	 * @return void
	 */
	public function register_dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'openconsent_cmp_records',
			__( 'OpenConsent CMP records', 'openconsent-cmp' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget() {
		$stats = $this->log_stats();
		?>
		<p>
			<strong><?php echo esc_html( number_format_i18n( $this->total_logs() ) ); ?></strong>
			<?php esc_html_e( 'consent records stored locally.', 'openconsent-cmp' ); ?>
		</p>
		<ul class="openconsent-dashboard-list">
			<li><?php esc_html_e( 'Accepted all:', 'openconsent-cmp' ); ?> <strong><?php echo esc_html( number_format_i18n( $stats['accept_all'] ) ); ?></strong></li>
			<li><?php esc_html_e( 'Necessary only:', 'openconsent-cmp' ); ?> <strong><?php echo esc_html( number_format_i18n( $stats['necessary_only'] ) ); ?></strong></li>
			<li><?php esc_html_e( 'Custom choices:', 'openconsent-cmp' ); ?> <strong><?php echo esc_html( number_format_i18n( $stats['save_choices'] ) ); ?></strong></li>
		</ul>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=openconsent-cmp' ) ); ?>"><?php esc_html_e( 'Open records', 'openconsent-cmp' ); ?></a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=openconsent_export_logs' ), 'openconsent_export_logs' ) ); ?>"><?php esc_html_e( 'Download CSV', 'openconsent-cmp' ); ?></a>
		</p>
		<p class="description">
			<?php esc_html_e( 'OpenConsent CMP is built by YASA LTD.', 'openconsent-cmp' ); ?>
			<a href="<?php echo esc_url( OPENCONSENT_CMP_AUTHOR_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit YASA LTD', 'openconsent-cmp' ); ?></a>
			<?php esc_html_e( 'or', 'openconsent-cmp' ); ?>
			<a href="<?php echo esc_url( OPENCONSENT_CMP_DONATION_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'support open source development', 'openconsent-cmp' ); ?></a>.
		</p>
		<?php
	}

	/**
	 * Get rows for export.
	 *
	 * @param array $filters Record filters.
	 * @return array
	 */
	private function export_rows( $filters = array() ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . OpenConsent_CMP::LOG_TABLE );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			wp_die( esc_html__( 'Consent log table does not exist yet.', 'openconsent-cmp' ) );
		}

		$filters = wp_parse_args( $filters, $this->record_filters( array() ) );
		$params  = array();
		$where   = $this->record_where_sql( $filters, $params );
		$sql     = "SELECT created_at, consent_id, consent_action, necessary, preferences, statistics, marketing, unclassified, region, region_mode, language, page_url, referrer_url, plugin_version, consent_hash, ip_hash, user_agent_hash, consent_json FROM {$table} WHERE {$where} ORDER BY id DESC";

		if ( $params ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name and WHERE fragments are built from whitelisted values above.
			return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name and WHERE fragments are built from whitelisted values above.
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Return export fields in stable order.
	 *
	 * @return array
	 */
	private function export_header_map() {
		return array(
			'created_at'      => '',
			'consent_id'      => '',
			'consent_action'  => '',
			'necessary'       => 1,
			'preferences'     => 0,
			'statistics'      => 0,
			'marketing'       => 0,
			'unclassified'    => 0,
			'region'          => '',
			'region_mode'     => '',
			'language'        => '',
			'page_url'        => '',
			'referrer_url'    => '',
			'plugin_version'  => '',
			'consent_hash'    => '',
			'ip_hash'         => '',
			'user_agent_hash' => '',
			'consent_json'    => '',
		);
	}

	/**
	 * Normalize export row so CSV and JSON use the same fields.
	 *
	 * @param array $row Database row.
	 * @return array
	 */
	private function normalize_export_row( $row ) {
		$defaults = $this->export_header_map();
		$clean    = array();

		foreach ( $defaults as $key => $default ) {
			$clean[ $key ] = isset( $row[ $key ] ) ? $row[ $key ] : $default;
		}

		return $clean;
	}
}
