<?php

/**
 * Plugin Name: Seofyme SEO
 * Plugin URI:  https://seofyme.com/wordpress
 * Description: Original all-in-one WordPress SEO — on-page guidance, sitemaps, schema, redirects, multi-keyphrase, internal linking, AI drafting, Local/Video/News SEO, llms.txt.
 * Version:     0.1.2
 * Author:      NOOBBase <wordpress-plugin@seofyme.com>
 * Author URI:  https://seofyme.com
 * Text Domain: seofyme-seo
 * Domain Path: /languages
 * License:     GPL-3.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package SeofymeSEO
 */

if (! defined('ABSPATH')) {
	exit;
}

define('SEOFYME_SEO_VERSION', '0.1.2');
define('SEOFYME_SEO_FILE', __FILE__);
define('SEOFYME_SEO_PATH', plugin_dir_path(__FILE__));
define('SEOFYME_SEO_URL', plugin_dir_url(__FILE__));
define('SEOFYME_SEO_BASENAME', plugin_basename(__FILE__));

require_once SEOFYME_SEO_PATH . 'includes/Autoloader.php';
SeofymeSEO\Autoloader::register(SEOFYME_SEO_PATH);

register_activation_hook(__FILE__, array('SeofymeSEO\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('SeofymeSEO\\Plugin', 'deactivate'));

add_action(
	'plugins_loaded',
	static function () {
		SeofymeSEO\Plugin::instance()->init();
	}
);
