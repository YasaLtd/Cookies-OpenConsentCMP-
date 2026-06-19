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
			'position'            => isset( $input['position'] ) && 'bottom' === $input['position'] ? 'bottom' : 'center',
			'accent_color'        => sanitize_hex_color( $input['accent_color'] ?? '' ) ?: '#54d2bf',
			'background_color'    => sanitize_hex_color( $input['background_color'] ?? '' ) ?: '#111827',
			'text_color'          => sanitize_hex_color( $input['text_color'] ?? '' ) ?: '#ffffff',
			'google_consent_mode' => empty( $input['google_consent_mode'] ) ? 0 : 1,
			'google_consent_behavior' => isset( $input['google_consent_behavior'] ) && 'basic' === $input['google_consent_behavior'] ? 'basic' : 'advanced',
			'url_passthrough'     => empty( $input['url_passthrough'] ) ? 0 : 1,
			'ads_data_redaction'  => empty( $input['ads_data_redaction'] ) ? 0 : 1,
			'log_retention_days'  => max( 1, absint( $input['log_retention_days'] ?? 365 ) ),
			'services'            => $this->sanitize_services( $input['services'] ?? '' ),
		);

		$options['scan_report']           = $current['scan_report'];
		$options['scan_report_generated'] = $current['scan_report_generated'];

		foreach ( array( 'category_preferences', 'category_statistics', 'category_marketing' ) as $key ) {
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

			$category = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing' ), true ) ? $parts[1] : 'marketing';
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
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'OpenConsent CMP', 'openconsent-cmp' ); ?></h1>
			<p><?php esc_html_e( 'A self-hosted consent manager for WordPress: present clear choices, categorize services, block optional scripts, publish a declaration, record consent choices, and send Google Consent Mode signals.', 'openconsent-cmp' ); ?></p>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Publisher ads note:', 'openconsent-cmp' ); ?></strong> <?php esc_html_e( 'Google requires a Google-certified CMP integrated with the IAB TCF when serving personalized AdSense, Ad Manager, or AdMob ads to users in the EEA, UK, or Switzerland. OpenConsent CMP is self-hosted and is not a Google-certified TCF CMP. Use it for publisher ads only after reviewing your ad mode, regions, and legal requirements.', 'openconsent-cmp' ); ?></p>
			</div>

			<?php if ( isset( $_GET['openconsent_scanned'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Homepage scan completed.', 'openconsent-cmp' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'openconsent_cmp' ); ?>

				<h2><?php esc_html_e( 'Banner', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable banner', 'openconsent-cmp' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[enabled]" value="1" <?php checked( $options['enabled'], 1 ); ?>> <?php esc_html_e( 'Show consent banner on the frontend', 'openconsent-cmp' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-title"><?php esc_html_e( 'Title', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-title" class="regular-text" type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[banner_title]" value="<?php echo esc_attr( $options['banner_title'] ); ?>"></td>
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
							<input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_accept]" value="<?php echo esc_attr( $options['button_accept'] ); ?>">
							<input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_reject]" value="<?php echo esc_attr( $options['button_reject'] ); ?>">
							<input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_save]" value="<?php echo esc_attr( $options['button_save'] ); ?>">
							<input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_customize]" value="<?php echo esc_attr( $options['button_customize'] ); ?>">
							<input type="text" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[button_revoke]" value="<?php echo esc_attr( $options['button_revoke'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Language', 'openconsent-cmp' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[auto_detect_language]" value="1" <?php checked( $options['auto_detect_language'], 1 ); ?>> <?php esc_html_e( 'Detect the visitor browser language for built-in banner labels', 'openconsent-cmp' ); ?></label>
							<p class="description"><?php esc_html_e( 'Custom text stays editable and is rendered as normal page text, so browser translation tools can translate it.', 'openconsent-cmp' ); ?></p>
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
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[ads_data_redaction]" value="1" <?php checked( $options['ads_data_redaction'], 1 ); ?>> <?php esc_html_e( 'Enable ads data redaction', 'openconsent-cmp' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[url_passthrough]" value="1" <?php checked( $options['url_passthrough'], 1 ); ?>> <?php esc_html_e( 'Enable URL passthrough', 'openconsent-cmp' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="openconsent-services"><?php esc_html_e( 'Service registry', 'openconsent-cmp' ); ?></label></th>
						<td>
							<textarea id="openconsent-services" class="large-text code" rows="9" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[services]"><?php echo esc_textarea( $options['services'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One service per line: URL pattern | category | display name. Categories: preferences, statistics, marketing.', 'openconsent-cmp' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Category descriptions', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( array( 'preferences', 'statistics', 'marketing' ) as $category ) : ?>
						<tr>
							<th scope="row"><label for="openconsent-<?php echo esc_attr( $category ); ?>"><?php echo esc_html( ucfirst( $category ) ); ?></label></th>
							<td><textarea id="openconsent-<?php echo esc_attr( $category ); ?>" class="large-text" rows="2" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[category_<?php echo esc_attr( $category ); ?>]"><?php echo esc_textarea( $options[ 'category_' . $category ] ); ?></textarea></td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'Audit log', 'openconsent-cmp' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="openconsent-retention"><?php esc_html_e( 'Retention', 'openconsent-cmp' ); ?></label></th>
						<td><input id="openconsent-retention" type="number" min="1" name="<?php echo esc_attr( OpenConsent_CMP::OPTION ); ?>[log_retention_days]" value="<?php echo esc_attr( $options['log_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'openconsent-cmp' ); ?></td>
					</tr>
				</table>

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
}
