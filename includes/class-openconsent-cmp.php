<?php
/**
 * Core plugin bootstrap and shared helpers.
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin service container.
 */
final class OpenConsent_CMP {
	const OPTION = 'openconsent_cmp_options';
	const LOG_TABLE = 'openconsent_cmp_logs';
	const DB_VERSION_OPTION = 'openconsent_cmp_db_version';
	const DB_VERSION = '2';

	/**
	 * Singleton instance.
	 *
	 * @var OpenConsent_CMP|null
	 */
	private static $instance = null;

	/**
	 * Admin service.
	 *
	 * @var OpenConsent_CMP_Admin
	 */
	public $admin;

	/**
	 * Frontend service.
	 *
	 * @var OpenConsent_CMP_Frontend
	 */
	public $frontend;

	/**
	 * Scanner service.
	 *
	 * @var OpenConsent_CMP_Scanner
	 */
	public $scanner;

	/**
	 * Return singleton instance.
	 *
	 * @return OpenConsent_CMP
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		load_plugin_textdomain( 'openconsent-cmp', false, dirname( plugin_basename( OPENCONSENT_CMP_FILE ) ) . '/languages' );

		$this->scanner  = new OpenConsent_CMP_Scanner();
		$this->frontend = new OpenConsent_CMP_Frontend( $this );
		$this->admin    = new OpenConsent_CMP_Admin( $this );

		add_action( 'wp_ajax_openconsent_log_consent', array( $this, 'ajax_log_consent' ) );
		add_action( 'wp_ajax_nopriv_openconsent_log_consent', array( $this, 'ajax_log_consent' ) );
		add_action( 'openconsent_cmp_cleanup_logs', array( $this, 'cleanup_logs' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade_database' ) );
		add_action( 'init', array( $this, 'register_wp_consent_api_cookie' ) );
		add_filter( 'wp_get_consent_type', array( $this, 'wp_consent_type' ) );
		add_filter( 'wp_consent_api_registered_' . plugin_basename( OPENCONSENT_CMP_FILE ), '__return_true' );
		add_filter( 'plugin_action_links_' . plugin_basename( OPENCONSENT_CMP_FILE ), array( $this, 'plugin_action_links' ) );
		add_shortcode( 'openconsent_declaration', array( $this, 'cookie_declaration_shortcode' ) );
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;

		$table = $wpdb->prefix . self::LOG_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::log_table_schema( $table, $charset_collate ) );
		self::backfill_log_columns( $table );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults() );
		}

		if ( ! wp_next_scheduled( 'openconsent_cmp_cleanup_logs' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'openconsent_cmp_cleanup_logs' );
		}

		set_transient( 'openconsent_cmp_activation_notice', 1, defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60 );
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'openconsent_cmp_cleanup_logs' );
	}

	/**
	 * Return the consent log table schema for install and upgrades.
	 *
	 * @param string $table           Full table name.
	 * @param string $charset_collate Database charset/collation.
	 * @return string
	 */
	private static function log_table_schema( $table, $charset_collate ) {
		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			consent_id varchar(64) NOT NULL,
			created_at datetime NOT NULL,
			consent_hash char(64) NOT NULL,
			consent_json longtext NOT NULL,
			consent_action varchar(32) NOT NULL DEFAULT '',
			necessary tinyint(1) NOT NULL DEFAULT 1,
			preferences tinyint(1) NOT NULL DEFAULT 0,
			statistics tinyint(1) NOT NULL DEFAULT 0,
			marketing tinyint(1) NOT NULL DEFAULT 0,
			unclassified tinyint(1) NOT NULL DEFAULT 0,
			region varchar(32) NOT NULL DEFAULT '',
			region_mode varchar(32) NOT NULL DEFAULT '',
			language varchar(20) NOT NULL DEFAULT '',
			page_url text NOT NULL,
			referrer_url text NOT NULL,
			plugin_version varchar(20) NOT NULL DEFAULT '',
			ip_hash char(64) NOT NULL,
			user_agent_hash char(64) NOT NULL,
			PRIMARY KEY  (id),
			KEY consent_id (consent_id),
			KEY created_at (created_at),
			KEY consent_action (consent_action),
			KEY region (region),
			KEY plugin_version (plugin_version)
		) {$charset_collate};";
	}

	/**
	 * Upgrade database schema for existing installs when needed.
	 *
	 * @return void
	 */
	public function maybe_upgrade_database() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::LOG_TABLE;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::log_table_schema( $table, $wpdb->get_charset_collate() ) );
		self::backfill_log_columns( $table );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Backfill structured columns for records written by older plugin versions.
	 *
	 * @param string $table Full table name.
	 * @return void
	 */
	private static function backfill_log_columns( $table ) {
		global $wpdb;

		$rows = $wpdb->get_results( "SELECT id, consent_json FROM {$table} WHERE (plugin_version = '' OR consent_action = '') AND consent_json <> '' LIMIT 500", ARRAY_A );
		foreach ( $rows as $row ) {
			$decoded = json_decode( $row['consent_json'], true );
			if ( ! is_array( $decoded ) ) {
				continue;
			}

			$wpdb->update(
				$table,
				array(
					'consent_action' => isset( $decoded['action'] ) ? sanitize_key( $decoded['action'] ) : 'save_choices',
					'necessary'      => 1,
					'preferences'    => ! empty( $decoded['preferences'] ) ? 1 : 0,
					'statistics'     => ! empty( $decoded['statistics'] ) ? 1 : 0,
					'marketing'      => ! empty( $decoded['marketing'] ) ? 1 : 0,
					'unclassified'   => ! empty( $decoded['unclassified'] ) ? 1 : 0,
					'region'         => isset( $decoded['region'] ) ? sanitize_key( $decoded['region'] ) : '',
					'region_mode'    => isset( $decoded['region_mode'] ) ? sanitize_key( $decoded['region_mode'] ) : ( isset( $decoded['regionMode'] ) ? sanitize_key( $decoded['regionMode'] ) : '' ),
					'language'       => isset( $decoded['language'] ) ? sanitize_text_field( $decoded['language'] ) : '',
					'plugin_version' => isset( $decoded['version'] ) ? sanitize_text_field( $decoded['version'] ) : '',
				),
				array( 'id' => absint( $row['id'] ) ),
				array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Default plugin options.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'               => 1,
			'blocking_mode'         => 'auto',
			'block_iframes'         => 1,
			'banner_title'          => 'Your privacy choices',
			'banner_message'        => 'We use cookies and similar technologies to keep this site reliable, measure usage, and improve marketing. Choose what you want to allow.',
			'party_disclosure'      => 'Google and other listed service providers may collect, receive, or use personal data when their services are enabled. Review the cookie declaration and privacy policy for details.',
			'privacy_url'           => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
			'button_accept'         => 'Accept all',
			'button_reject'         => 'Necessary only',
			'button_save'           => 'Save choices',
			'button_customize'      => 'Customize',
			'button_revoke'         => 'Privacy choices',
			'auto_detect_language'  => 1,
			'banner_language'       => 'auto',
			'region_mode'           => 'strict',
			'default_region'        => 'eea',
			'consent_model'         => 'opt_in',
			'position'              => 'center',
			'accent_color'          => '#54d2bf',
			'background_color'      => '#111827',
			'text_color'            => '#ffffff',
			'google_consent_mode'   => 1,
			'google_consent_behavior' => 'advanced',
			'google_signal_map_ad_storage' => 'marketing',
			'google_signal_map_ad_user_data' => 'marketing',
			'google_signal_map_ad_personalization' => 'marketing',
			'google_signal_map_analytics_storage' => 'statistics',
			'google_signal_map_functionality_storage' => 'preferences',
			'google_signal_map_personalization_storage' => 'preferences',
			'url_passthrough'       => 0,
			'ads_data_redaction'    => 1,
			'wp_consent_api'        => 1,
			'log_retention_days'    => 365,
			'services'              => "google-analytics.com|statistics|Google Analytics|Google LLC|Audience measurement and analytics.|https://policies.google.com/privacy\nwww.googletagmanager.com|statistics|Google Tag Manager|Google LLC|Tag loading and consent-aware measurement.|https://policies.google.com/privacy\nconnect.facebook.net|marketing|Meta Pixel|Meta Platforms Ireland Limited|Advertising measurement and remarketing.|https://www.facebook.com/privacy/policy/\ndoubleclick.net|marketing|Google Ads|Google LLC|Advertising measurement and remarketing.|https://policies.google.com/privacy\npagead2.googlesyndication.com|marketing|Google AdSense|Google LLC|Publisher advertising and ad measurement.|https://policies.google.com/privacy\ngooglesyndication.com|marketing|Google publisher ads|Google LLC|Publisher advertising and ad delivery.|https://policies.google.com/privacy\nyoutube.com|marketing|YouTube embeds|Google LLC|Embedded video playback and related measurement.|https://policies.google.com/privacy\nvimeo.com|marketing|Vimeo embeds|Vimeo.com, Inc.|Embedded video playback and related measurement.|https://vimeo.com/privacy",
			'script_handles'        => '',
			'category_preferences'  => 'Preferences cookies remember choices such as language, region, and interface settings.',
			'category_statistics'   => 'Statistics cookies help us understand how visitors use the site.',
			'category_marketing'    => 'Marketing cookies support advertising, measurement, and embedded media.',
			'category_unclassified' => 'Unclassified services are blocked until they are reviewed and assigned to a clear category.',
			'scan_report'           => array(),
			'scan_report_generated' => '',
		);
	}

	/**
	 * Get merged options.
	 *
	 * @return array
	 */
	public function options() {
		$options = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), self::defaults() );
	}

	/**
	 * Return configured services as structured rows.
	 *
	 * @return array
	 */
	public function services() {
		$options = $this->options();
		$rows    = preg_split( '/\r\n|\r|\n/', (string) $options['services'] );
		$items   = array();

		foreach ( $rows as $row ) {
			$parts = array_map( 'trim', explode( '|', $row ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}

			$category = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ? $parts[1] : 'unclassified';
			$metadata = $this->service_metadata_defaults( $parts[0] );
			$items[]  = array(
				'pattern'     => $parts[0],
				'category'    => $category,
				'name'        => isset( $parts[2] ) && '' !== $parts[2] ? $parts[2] : $parts[0],
				'provider'    => isset( $parts[3] ) && '' !== $parts[3] ? $parts[3] : $metadata['provider'],
				'purpose'     => isset( $parts[4] ) && '' !== $parts[4] ? $parts[4] : $metadata['purpose'],
				'privacy_url' => isset( $parts[5] ) && '' !== $parts[5] ? esc_url_raw( $parts[5] ) : $metadata['privacy_url'],
			);
		}

		return $items;
	}

	/**
	 * Return disclosure defaults for common bundled service patterns.
	 *
	 * @param string $pattern Service match pattern.
	 * @return array
	 */
	private function service_metadata_defaults( $pattern ) {
		$pattern = strtolower( $pattern );
		$defaults = array(
			'google-analytics.com'        => array( 'Google LLC', 'Audience measurement and analytics.', 'https://policies.google.com/privacy' ),
			'www.googletagmanager.com'   => array( 'Google LLC', 'Tag loading and consent-aware measurement.', 'https://policies.google.com/privacy' ),
			'connect.facebook.net'       => array( 'Meta Platforms Ireland Limited', 'Advertising measurement and remarketing.', 'https://www.facebook.com/privacy/policy/' ),
			'doubleclick.net'            => array( 'Google LLC', 'Advertising measurement and remarketing.', 'https://policies.google.com/privacy' ),
			'pagead2.googlesyndication.com' => array( 'Google LLC', 'Publisher advertising and ad measurement.', 'https://policies.google.com/privacy' ),
			'googlesyndication.com'      => array( 'Google LLC', 'Publisher advertising and ad delivery.', 'https://policies.google.com/privacy' ),
			'youtube.com'                => array( 'Google LLC', 'Embedded video playback and related measurement.', 'https://policies.google.com/privacy' ),
			'vimeo.com'                  => array( 'Vimeo.com, Inc.', 'Embedded video playback and related measurement.', 'https://vimeo.com/privacy' ),
		);

		foreach ( $defaults as $needle => $values ) {
			if ( false !== strpos( $pattern, $needle ) ) {
				return array(
					'provider'    => $values[0],
					'purpose'     => $values[1],
					'privacy_url' => $values[2],
				);
			}
		}

		return array(
			'provider'    => '',
			'purpose'     => '',
			'privacy_url' => '',
		);
	}

	/**
	 * Return configured WordPress script handles as structured rows.
	 *
	 * @return array
	 */
	public function script_handles() {
		$options = $this->options();
		$rows    = preg_split( '/\r\n|\r|\n/', (string) $options['script_handles'] );
		$items   = array();

		foreach ( $rows as $row ) {
			$parts = array_map( 'trim', explode( '|', $row ) );
			if ( count( $parts ) < 2 || '' === $parts[0] ) {
				continue;
			}

			$category = in_array( $parts[1], array( 'preferences', 'statistics', 'marketing', 'unclassified' ), true ) ? $parts[1] : 'unclassified';
			$items[]  = array(
				'handle'   => sanitize_key( $parts[0] ),
				'category' => $category,
				'name'     => isset( $parts[2] ) && '' !== $parts[2] ? $parts[2] : $parts[0],
			);
		}

		return $items;
	}

	/**
	 * Return this site's consent type for the WP Consent API plugin.
	 *
	 * @param string $type Current consent type.
	 * @return string
	 */
	public function wp_consent_type( $type ) {
		$options = $this->options();
		if ( empty( $options['enabled'] ) || empty( $options['wp_consent_api'] ) ) {
			return $type;
		}

		return 'opt_out' === $options['consent_model'] && 'strict' !== $options['region_mode'] ? 'optout' : 'optin';
	}

	/**
	 * Register OpenConsent's own cookie with the WP Consent API cookie inventory.
	 *
	 * @return void
	 */
	public function register_wp_consent_api_cookie() {
		$options = $this->options();
		if ( empty( $options['enabled'] ) || empty( $options['wp_consent_api'] ) || ! function_exists( 'wp_add_cookie_info' ) ) {
			return;
		}

		wp_add_cookie_info(
			'openconsent_cmp',
			'OpenConsent CMP',
			'functional',
			'1 year',
			__( 'Stores the visitor consent choice so the banner can remember and apply selected categories.', 'openconsent-cmp' ),
			'',
			false,
			false,
			'HTTP',
			wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ?: ''
		);
	}

	/**
	 * Add plugin row actions.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'options-general.php?page=openconsent-cmp' ) ) . '">' . esc_html__( 'Settings', 'openconsent-cmp' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Log visitor consent from AJAX.
	 *
	 * @return void
	 */
	public function ajax_log_consent() {
		check_ajax_referer( 'openconsent-cmp', 'nonce' );

		$raw = isset( $_POST['consent'] ) ? wp_unslash( $_POST['consent'] ) : '';
		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => 'Invalid consent payload.' ), 400 );
		}

		$allowed = array(
			'necessary'   => true,
			'preferences' => ! empty( $decoded['preferences'] ),
			'statistics'  => ! empty( $decoded['statistics'] ),
			'marketing'   => ! empty( $decoded['marketing'] ),
			'unclassified' => ! empty( $decoded['unclassified'] ),
			'region'      => isset( $decoded['region'] ) ? sanitize_key( $decoded['region'] ) : '',
			'region_mode' => isset( $decoded['regionMode'] ) ? sanitize_key( $decoded['regionMode'] ) : '',
			'action'      => isset( $decoded['action'] ) ? sanitize_key( $decoded['action'] ) : 'save_choices',
			'language'    => isset( $decoded['language'] ) ? sanitize_text_field( $decoded['language'] ) : '',
			'version'     => OPENCONSENT_CMP_VERSION,
		);

		$consent_json = wp_json_encode( $allowed );
		$consent_id   = isset( $decoded['id'] ) ? sanitize_text_field( $decoded['id'] ) : wp_generate_uuid4();
		$page_url     = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		$referrer_url = isset( $_POST['referrer_url'] ) ? esc_url_raw( wp_unslash( $_POST['referrer_url'] ) ) : '';

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::LOG_TABLE,
			array(
				'consent_id'      => $consent_id,
				'created_at'      => current_time( 'mysql', true ),
				'consent_hash'    => hash( 'sha256', $consent_json ),
				'consent_json'    => $consent_json,
				'consent_action'  => $allowed['action'],
				'necessary'       => 1,
				'preferences'     => $allowed['preferences'] ? 1 : 0,
				'statistics'      => $allowed['statistics'] ? 1 : 0,
				'marketing'       => $allowed['marketing'] ? 1 : 0,
				'unclassified'    => $allowed['unclassified'] ? 1 : 0,
				'region'          => $allowed['region'],
				'region_mode'     => $allowed['region_mode'],
				'language'        => $allowed['language'],
				'page_url'        => $page_url,
				'referrer_url'    => $referrer_url,
				'plugin_version'  => OPENCONSENT_CMP_VERSION,
				'ip_hash'         => hash( 'sha256', $this->remote_addr() . wp_salt( 'nonce' ) ),
				'user_agent_hash' => hash( 'sha256', ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) . wp_salt( 'auth' ) ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success( array( 'consent_id' => $consent_id ) );
	}

	/**
	 * Render cookie declaration shortcode.
	 *
	 * @return string
	 */
	public function cookie_declaration_shortcode() {
		$services = $this->services();
		$options  = $this->options();
		$scan     = is_array( $options['scan_report'] ) ? $options['scan_report'] : array();

		ob_start();
		?>
		<div class="openconsent-declaration">
			<h2><?php esc_html_e( 'Cookie declaration', 'openconsent-cmp' ); ?></h2>
			<p><?php esc_html_e( 'This declaration is generated from the configured service registry and the latest local scan report.', 'openconsent-cmp' ); ?></p>
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Service', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Category', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Provider', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Purpose', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Match pattern', 'openconsent-cmp' ); ?></th>
						<th><?php esc_html_e( 'Provider policy', 'openconsent-cmp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $services as $service ) : ?>
						<tr>
							<td><?php echo esc_html( $service['name'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $service['category'] ) ); ?></td>
							<td><?php echo esc_html( $service['provider'] ?: __( 'Not specified', 'openconsent-cmp' ) ); ?></td>
							<td><?php echo esc_html( $service['purpose'] ?: __( 'Review needed', 'openconsent-cmp' ) ); ?></td>
							<td><code><?php echo esc_html( $service['pattern'] ); ?></code></td>
							<td>
								<?php if ( ! empty( $service['privacy_url'] ) ) : ?>
									<a href="<?php echo esc_url( $service['privacy_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open policy', 'openconsent-cmp' ); ?></a>
								<?php else : ?>
									<?php esc_html_e( 'Not specified', 'openconsent-cmp' ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( ! empty( $scan ) ) : ?>
				<h3><?php esc_html_e( 'Latest scan findings', 'openconsent-cmp' ); ?></h3>
				<ul>
					<?php foreach ( $scan as $item ) : ?>
						<li><code><?php echo esc_html( $item ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Delete logs beyond configured retention.
	 *
	 * @return void
	 */
	public function cleanup_logs() {
		$options = $this->options();
		$days    = max( 1, absint( $options['log_retention_days'] ) );

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}" . self::LOG_TABLE . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
			)
		);
	}

	/**
	 * Add suggested privacy policy text in WordPress privacy tools.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . esc_html__( 'OpenConsent CMP stores visitor consent choices in a first-party browser cookie and records anonymized consent logs in the local WordPress database. These logs include a consent ID, selected categories, timestamp, consent hash, and salted hashes of the visitor IP address and user agent. The plugin can block or enable optional scripts according to the visitor choices and the configured service registry.', 'openconsent-cmp' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Site owners should list the third-party services they use, describe each category clearly, provide a way to change or revoke consent, and choose a suitable log retention period.', 'openconsent-cmp' ) . '</p>';

		wp_add_privacy_policy_content( 'OpenConsent CMP', wp_kses_post( wpautop( $content ) ) );
	}

	/**
	 * Get remote address safely.
	 *
	 * @return string
	 */
	private function remote_addr() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}
