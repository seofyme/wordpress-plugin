<?php
/**
 * Uninstall cleanup.
 *
 * @package SeofymeSEO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Notify CacheRocket that this install is being removed/disconnected.
 *
 * Best-effort: failures must not block local cleanup. Runs while credentials
 * still exist. Uses the shared WordPress install lifecycle API (not the
 * Seofyme AI gateway).
 */
function seofyme_seo_uninstall_notify_disconnect() {
	$options = get_option( 'seofyme_seo_options', array() );
	if ( ! is_array( $options ) ) {
		return;
	}

	$public = isset( $options['seofyme_public_key'] ) ? (string) $options['seofyme_public_key'] : '';
	$secret = isset( $options['seofyme_secret_key'] ) ? (string) $options['seofyme_secret_key'] : '';
	if ( '' === $public || '' === $secret ) {
		return;
	}

	$host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$domain = is_string( $host ) ? strtolower( $host ) : '';
	if ( '' === $domain ) {
		return;
	}

	$base = 'https://api.cacherocket.com/web/v1/wordpress';
	/**
	 * Filter the WordPress install lifecycle API base (heartbeat / disconnect).
	 *
	 * @param string $base Default production wordpress gateway path.
	 */
	$base = apply_filters( 'seofyme_wordpress_api_base', $base );
	$base = is_string( $base ) ? untrailingslashit( $base ) : 'https://api.cacherocket.com/web/v1/wordpress';

	wp_remote_post(
		$base . '/pluginDisconnect',
		array(
			'headers'  => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'Seofyme-SEO-WordPress-Uninstall',
				'Accept'       => 'application/json',
				'X-Product'    => 'seofyme',
			),
			'body'     => wp_json_encode(
				array(
					'publicKey' => $public,
					'secretKey' => $secret,
					'siteUrl'   => home_url( '/' ),
					'domain'    => $domain,
					'product'   => 'seofyme',
				)
			),
			'timeout'  => 15,
			'blocking' => true,
		)
	);
}

seofyme_seo_uninstall_notify_disconnect();

delete_option( 'seofyme_seo_options' );
delete_option( 'seofyme_rank_keywords' );
delete_option( 'seofyme_gsc_auth' );
delete_option( 'seofyme_cloud_account_last_good' );
delete_option( 'seofyme_cloud_account_error' );
delete_option( 'seofyme_last_heartbeat' );
delete_transient( 'seofyme_cloud_account_status' );
delete_transient( 'seofyme_heartbeat_sent' );

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Plugin table drop on uninstall.
$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $wpdb->prefix . 'seofyme_redirects' ) . '`' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Plugin table drop on uninstall.
$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $wpdb->prefix . 'seofyme_404' ) . '`' );
