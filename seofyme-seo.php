<?php
/**
 * Seofyme SEO Plugin.
 *
 * Based on Yoast SEO (GPL v3). See ATTRIBUTION.md for license details.
 *
 * @package   Seofyme\SEO
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: Seofyme SEO
 * Version:     1.0.0
 * Plugin URI:  https://github.com/seofyme/wordpress-plugin
 * Description: All-in-one WordPress SEO — content analysis, XML sitemaps, schema, redirects, multi-keyphrase optimization, internal linking, AI drafting, Local/Video/News SEO, and more.
 * Author:      Seofyme
 * Author URI:  https://github.com/seofyme
 * Text Domain: seofyme-seo
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 6.8
 * Requires PHP: 7.4
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! defined( 'SEOFYME_SEO_FILE' ) ) {
	define( 'SEOFYME_SEO_FILE', __FILE__ );
}

if ( ! defined( 'SEOFYME_SEO_VERSION' ) ) {
	define( 'SEOFYME_SEO_VERSION', '1.0.0' );
}

if ( ! defined( 'SEOFYME_SEO_PATH' ) ) {
	define( 'SEOFYME_SEO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SEOFYME_SEO_URL' ) ) {
	define( 'SEOFYME_SEO_URL', plugin_dir_url( __FILE__ ) );
}

// Premium features are bundled (no separate paid addon required).
if ( ! defined( 'WPSEO_PREMIUM_FILE' ) ) {
	define( 'WPSEO_PREMIUM_FILE', __FILE__ );
}

if ( ! defined( 'WPSEO_PREMIUM_VERSION' ) ) {
	define( 'WPSEO_PREMIUM_VERSION', SEOFYME_SEO_VERSION );
}

if ( ! defined( 'SEOFYME_PREMIUM_FILE' ) ) {
	define( 'SEOFYME_PREMIUM_FILE', __FILE__ );
}

if ( ! defined( 'WPSEO_FILE' ) ) {
	define( 'WPSEO_FILE', __FILE__ );
}

// Load the forked free core (GPL) bootstrap.
require_once dirname( __FILE__ ) . '/wp-seo-main.php';

// Load Seofyme premium feature modules (original implementations).
require_once dirname( __FILE__ ) . '/premium/bootstrap.php';
