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
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall cleanup removes the plugin-owned custom consent log table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}openconsent_cmp_logs" );
