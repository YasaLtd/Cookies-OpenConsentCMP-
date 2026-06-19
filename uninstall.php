<?php
/**
 * Uninstall cleanup.
 *
 * @package OpenConsentCMP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'openconsent_cmp_options' );

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}openconsent_cmp_logs" );
