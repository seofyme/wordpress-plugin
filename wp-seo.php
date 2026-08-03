<?php
/**
 * Legacy bootstrap stub.
 *
 * WordPress should load `seofyme-seo.php` as the plugin entry point.
 * This file remains for path compatibility with the forked GPL core.
 *
 * @package Seofyme\SEO
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Prefer the Seofyme entry point.
if ( ! defined( 'WPSEO_FILE' ) ) {
	require_once dirname( __FILE__ ) . '/seofyme-seo.php';
}
