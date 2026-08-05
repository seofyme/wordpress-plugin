<?php
/**
 * Uninstall cleanup.
 *
 * @package SeofymeSEO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'seofyme_seo_options' );

global $wpdb;
$seofyme_redirects_table = $wpdb->prefix . 'seofyme_redirects';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$seofyme_redirects_table}" );
