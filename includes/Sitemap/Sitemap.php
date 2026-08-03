<?php
/**
 * XML sitemap generation.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Sitemap;

use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves /sitemap.xml and per-type sitemaps.
 */
class Sitemap {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'rewrites' ) );
		add_action( 'template_redirect', array( $this, 'render' ) );
	}

	/**
	 * Rewrites.
	 *
	 * @return void
	 */
	public function rewrites() {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?seofyme_sitemap=index', 'top' );
		add_rewrite_rule( '^sitemap-([^/]+)\.xml$', 'index.php?seofyme_sitemap=$matches[1]', 'top' );
		add_rewrite_tag( '%seofyme_sitemap%', '([^&]+)' );
	}

	/**
	 * Render sitemap response.
	 *
	 * @return void
	 */
	public function render() {
		$type = get_query_var( 'seofyme_sitemap' );
		if ( ! $type || ! Options::get( 'xml_sitemap' ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		if ( 'index' === $type ) {
			$this->render_index();
			exit;
		}

		$this->render_type( sanitize_key( $type ) );
		exit;
	}

	/**
	 * Sitemap index.
	 *
	 * @return void
	 */
	private function render_index() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ( $types as $type ) {
			$loc = home_url( '/sitemap-' . $type . '.xml' );
			echo '<sitemap><loc>' . esc_url( $loc ) . '</loc></sitemap>';
		}
		echo '<sitemap><loc>' . esc_url( home_url( '/video-sitemap.xml' ) ) . '</loc></sitemap>';
		echo '<sitemap><loc>' . esc_url( home_url( '/news-sitemap.xml' ) ) . '</loc></sitemap>';
		echo '</sitemapindex>';
	}

	/**
	 * Per post-type sitemap.
	 *
	 * @param string $type Post type.
	 * @return void
	 */
	private function render_type( $type ) {
		if ( ! post_type_exists( $type ) ) {
			status_header( 404 );
			echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
			return;
		}

		$q = new \WP_Query(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'modified',
				'no_found_rows'  => true,
			)
		);

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ( $q->posts as $post ) {
			$robots = Post_Meta::get( $post->ID, Post_Meta::ROBOTS, 'index,follow' );
			if ( false !== strpos( $robots, 'noindex' ) ) {
				continue;
			}
			echo '<url>';
			echo '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>';
			echo '<lastmod>' . esc_html( get_the_modified_date( 'c', $post ) ) . '</lastmod>';
			echo '</url>';
		}
		echo '</urlset>';
	}
}
