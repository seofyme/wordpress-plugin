<?php
/**
 * Seofyme SEO Premium bootstrap.
 *
 * Original implementations of premium-parity features. Not derived from
 * Yoast SEO Premium (proprietary).
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SEOFYME_PREMIUM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEOFYME_PREMIUM_URL', plugin_dir_url( __FILE__ ) );

require_once SEOFYME_PREMIUM_PATH . 'class-autoloader.php';
require_once SEOFYME_PREMIUM_PATH . 'class-premium.php';

add_action(
	'plugins_loaded',
	static function () {
		Seofyme_Premium::instance()->init();
	},
	20
);

register_activation_hook(
	SEOFYME_SEO_FILE,
	static function () {
		require_once SEOFYME_PREMIUM_PATH . 'redirects/class-redirect-repository.php';
		Seofyme_Redirect_Repository::install();
		flush_rewrite_rules();
	}
);
