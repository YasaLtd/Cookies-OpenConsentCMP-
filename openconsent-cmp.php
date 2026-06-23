<?php
/**
 * Plugin Name: OpenConsent CMP
 * Description: Open source cookie consent management for WordPress with consent categories, prior blocking, consent logs, cookie declaration, and Google Consent Mode v2 signals.
 * Version: 1.1.5
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author: YASA LTD
 * Author URI: https://yasa.fi/
 * Plugin URI: https://cookies.yasa.fi/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: openconsent-cmp
 * Domain Path: /languages
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OPENCONSENT_CMP_VERSION', '1.1.5' );
define( 'OPENCONSENT_CMP_FILE', __FILE__ );
define( 'OPENCONSENT_CMP_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENCONSENT_CMP_URL', plugin_dir_url( __FILE__ ) );
define( 'OPENCONSENT_CMP_AUTHOR_URL', 'https://yasa.fi/' );
define( 'OPENCONSENT_CMP_DONATION_URL', 'https://buymeacoffee.com/anteryasa/e/550479' );

require_once OPENCONSENT_CMP_DIR . 'includes/class-openconsent-cmp.php';
require_once OPENCONSENT_CMP_DIR . 'includes/class-openconsent-cmp-admin.php';
require_once OPENCONSENT_CMP_DIR . 'includes/class-openconsent-cmp-frontend.php';
require_once OPENCONSENT_CMP_DIR . 'includes/class-openconsent-cmp-scanner.php';

register_activation_hook( __FILE__, array( 'OpenConsent_CMP', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OpenConsent_CMP', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		OpenConsent_CMP::instance();
	}
);
