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
$table = $wpdb->prefix . 'seofyme_redirects';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
