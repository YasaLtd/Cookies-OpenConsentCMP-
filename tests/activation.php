<?php
/**
 * Local activation check for OpenConsent CMP.
 *
 * Run from a WordPress install:
 * php wp-content/plugins/openconsent-cmp/tests/activation.php
 *
 * @package OpenConsentCMP
 */

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! file_exists( $wp_load ) ) {
	fwrite( STDERR, "WordPress bootstrap not found.\n" );
	exit( 1 );
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

deactivate_plugins( 'openconsent-cmp/openconsent-cmp.php', true );
$result = activate_plugin( 'openconsent-cmp/openconsent-cmp.php' );

if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . "\n" );
	exit( 1 );
}

echo "OpenConsent CMP activation check passed.\n";
