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
delete_option( 'seofyme_rank_keywords' );
delete_option( 'seofyme_cloud_account_last_good' );
delete_option( 'seofyme_cloud_account_error' );
delete_transient( 'seofyme_cloud_account_status' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Plugin table drop on uninstall.
$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $wpdb->prefix . 'seofyme_redirects' ) . '`' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Plugin table drop on uninstall.
$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $wpdb->prefix . 'seofyme_404' ) . '`' );
