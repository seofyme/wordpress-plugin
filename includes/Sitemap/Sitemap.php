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

		$prev_lang = apply_filters( 'wpml_current_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.

		$args = apply_filters(
			'seofyme_sitemap_query_args',
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'modified',
				'no_found_rows'  => true,
			)
		);

		$q = new \WP_Query( $args );

		$urlset_attrs = apply_filters(
			'seofyme_sitemap_urlset_attrs',
			array(
				'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
			)
		);
		if ( ! is_array( $urlset_attrs ) ) {
			$urlset_attrs = array(
				'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
			);
		}

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset';
		foreach ( $urlset_attrs as $attr_name => $attr_value ) {
			$attr_name = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $attr_name );
			if ( '' === $attr_name ) {
				continue;
			}
			echo ' ' . esc_attr( $attr_name ) . '="' . esc_attr( (string) $attr_value ) . '"';
		}
		echo '>';
		foreach ( $q->posts as $post ) {
			$robots = Post_Meta::get( $post->ID, Post_Meta::ROBOTS, 'index,follow' );
			if ( false !== strpos( $robots, 'noindex' ) ) {
				continue;
			}
			$inner  = '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>';
			$inner .= '<lastmod>' . esc_xml( get_the_modified_date( 'c', $post ) ) . '</lastmod>';
			/**
			 * Filter inner <url> markup (e.g. WPML xhtml:link alternates).
			 *
			 * Callbacks must return escaped XML only.
			 *
			 * @param string   $inner Inner XML.
			 * @param \WP_Post $post  Post.
			 */
			$inner = apply_filters( 'seofyme_sitemap_url_entry', $inner, $post );
			echo '<url>' . $inner . '</url>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url/esc_xml; filter must return escaped XML.
		}
		echo '</urlset>';

		if ( $prev_lang ) {
			do_action( 'wpml_switch_language', $prev_lang ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		}
	}
}
