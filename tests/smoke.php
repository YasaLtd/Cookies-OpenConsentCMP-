<?php
/**
 * Local smoke checks for OpenConsent CMP.
 *
 * Run from a WordPress install:
 * php wp-content/plugins/openconsent-cmp/tests/smoke.php
 *
 * @package OpenConsentCMP
 */

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! file_exists( $wp_load ) ) {
	fwrite( STDERR, "WordPress bootstrap not found.\n" );
	exit( 1 );
}

require_once $wp_load;

if ( ! defined( 'OPENCONSENT_CMP_VERSION' ) ) {
	fwrite( STDERR, "Plugin did not load.\n" );
	exit( 1 );
}

if ( '1.1.3' !== OPENCONSENT_CMP_VERSION ) {
	fwrite( STDERR, "Unexpected version: " . OPENCONSENT_CMP_VERSION . "\n" );
	exit( 1 );
}

$options = OpenConsent_CMP::instance()->options();
foreach ( array( 'debug_mode', 'scan_page_limit', 'services' ) as $key ) {
	if ( ! array_key_exists( $key, $options ) ) {
		fwrite( STDERR, "Missing option key: {$key}\n" );
		exit( 1 );
	}
}

$scanner = new OpenConsent_CMP_Scanner();
$report  = $scanner->scan_site( 1 );
if ( empty( $report['version'] ) || 2 !== (int) $report['version'] || empty( $report['summary'] ) ) {
	fwrite( STDERR, "Scanner did not return a structured report.\n" );
	exit( 1 );
}

$admin  = new OpenConsent_CMP_Admin( OpenConsent_CMP::instance() );
$method = new ReflectionMethod( $admin, 'service_line_from_csv_row' );
$method->setAccessible( true );
if ( '' !== $method->invoke( $admin, array( 'pattern', 'category', 'name', 'provider', 'purpose', 'privacy_url' ) ) ) {
	fwrite( STDERR, "Service CSV parser did not skip a header row.\n" );
	exit( 1 );
}

$line = $method->invoke( $admin, array( 'analytics.example.test', 'statistics', 'Example Analytics', 'Example Ltd', 'Audience measurement.', 'https://example.test/privacy' ) );
if ( 'analytics.example.test|statistics|Example Analytics|Example Ltd|Audience measurement.|https://example.test/privacy' !== $line ) {
	fwrite( STDERR, "Service CSV parser did not normalize a valid row.\n" );
	exit( 1 );
}

echo "OpenConsent CMP smoke checks passed.\n";
