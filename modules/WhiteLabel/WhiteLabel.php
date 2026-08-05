<?php
/**
 * White-label branding for agencies.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\WhiteLabel;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renames menu labels / hides Seofyme branding when enabled.
 */
class WhiteLabel {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'relabel' ), 999 );
		add_filter( 'all_plugins', array( $this, 'filter_plugins' ) );
	}

	/**
	 * Whether enabled.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) Options::get( 'whitelabel_enabled', false );
	}

	/**
	 * Brand name.
	 *
	 * @return string
	 */
	public static function brand() {
		$name = Options::get( 'whitelabel_name', '' );
		return $name ? $name : 'Seofyme SEO';
	}

	/**
	 * Relabel top-level menu.
	 *
	 * @return void
	 */
	public function relabel() {
		if ( ! self::enabled() ) {
			return;
		}
		global $menu, $submenu;
		foreach ( (array) $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'seofyme-seo' === $item[2] ) {
				$menu[ $i ][0] = self::brand();
			}
		}
		if ( isset( $submenu['seofyme-seo'] ) ) {
			foreach ( $submenu['seofyme-seo'] as $i => $item ) {
				if ( isset( $item[2] ) && 'seofyme-seo' === $item[2] ) {
					$submenu['seofyme-seo'][ $i ][0] = __( 'Dashboard', 'seofyme-seo' );
				}
			}
		}
	}

	/**
	 * Optionally hide plugin row details for clients.
	 *
	 * @param array $plugins Plugins.
	 * @return array
	 */
	public function filter_plugins( $plugins ) {
		if ( ! self::enabled() || current_user_can( 'manage_options' ) ) {
			return $plugins;
		}
		$key = SEOFYME_SEO_BASENAME;
		if ( isset( $plugins[ $key ] ) ) {
			$plugins[ $key ]['Name']        = self::brand();
			$plugins[ $key ]['Description'] = '';
			$plugins[ $key ]['Author']      = '';
			$plugins[ $key ]['AuthorURI']   = '';
			$plugins[ $key ]['PluginURI']   = '';
		}
		return $plugins;
	}
}
