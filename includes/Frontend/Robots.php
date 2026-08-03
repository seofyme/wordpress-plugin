<?php
/**
 * robots.txt enhancements.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Frontend;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appends sitemap URL to robots.txt.
 */
class Robots {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'robots_txt', array( $this, 'filter' ), 10, 2 );
	}

	/**
	 * Filter robots.txt.
	 *
	 * @param string $output Output.
	 * @param bool   $public Public.
	 * @return string
	 */
	public function filter( $output, $public ) {
		if ( ! $public || ! Options::get( 'xml_sitemap' ) ) {
			return $output;
		}
		$output .= "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
		return $output;
	}
}
