<?php
/**
 * WPML multilingual compatibility.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Compatibility;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hreflang + language-aware sitemap when WPML is active.
 *
 * Field translation is driven by wpml-config.xml in the plugin root.
 */
class WPML {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! self::is_active() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'hreflang' ), 2 );
		add_filter( 'seofyme_sitemap_query_args', array( $this, 'sitemap_query_args' ) );
		add_filter( 'seofyme_sitemap_url_entry', array( $this, 'sitemap_url_entry' ), 10, 2 );
		add_filter( 'seofyme_sitemap_urlset_attrs', array( $this, 'sitemap_urlset_attrs' ) );
	}

	/**
	 * Whether WPML (SitePress) is available.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_object_id' );
	}

	/**
	 * Output alternate hreflang tags unless WPML already does.
	 *
	 * @return void
	 */
	public function hreflang() {
		if ( self::wpml_outputs_hreflang() ) {
			return;
		}

		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => true ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		if ( ! is_array( $languages ) || count( $languages ) < 2 ) {
			return;
		}

		$default = apply_filters( 'wpml_default_language', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		foreach ( $languages as $lang ) {
			if ( empty( $lang['language_code'] ) || empty( $lang['url'] ) ) {
				continue;
			}
			$code = (string) $lang['language_code'];
			$url  = (string) $lang['url'];
			echo '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( $url ) . '" />' . "\n";
		}

		if ( $default && isset( $languages[ $default ]['url'] ) ) {
			echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $languages[ $default ]['url'] ) . '" />' . "\n";
		}
	}

	/**
	 * True when WPML (or WPML SEO) already prints head hreflangs.
	 *
	 * @return bool
	 */
	private static function wpml_outputs_hreflang() {
		$seo = apply_filters( 'wpml_setting', null, 'seo' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		if ( is_array( $seo ) && ! empty( $seo['head_langs'] ) ) {
			return true;
		}

		// WPML SEO add-on / older setting shapes.
		if ( defined( 'WPML_SEO_VERSION' ) && apply_filters( 'wpml_setting', false, 'seo_head_langs' ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
			return true;
		}

		return (bool) apply_filters( 'seofyme_skip_wpml_hreflang', false );
	}

	/**
	 * Include every language in sitemap queries.
	 *
	 * @param array $args WP_Query args.
	 * @return array
	 */
	public function sitemap_query_args( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$args['suppress_filters'] = false;

		// Ask WPML not to limit the query to the current language.
		do_action( 'wpml_switch_language', 'all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.

		return $args;
	}

	/**
	 * xmlns for xhtml:link alternates.
	 *
	 * @param array<string, string> $attrs Existing attribute map.
	 * @return array<string, string>
	 */
	public function sitemap_urlset_attrs( $attrs ) {
		if ( ! is_array( $attrs ) ) {
			$attrs = array(
				'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
			);
		}
		if ( empty( $attrs['xmlns:xhtml'] ) ) {
			$attrs['xmlns:xhtml'] = 'http://www.w3.org/1999/xhtml';
		}
		return $attrs;
	}

	/**
	 * Append hreflang xhtml:link nodes for a sitemap URL.
	 *
	 * @param string   $xml Inner <url> markup without wrapper.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function sitemap_url_entry( $xml, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return $xml;
		}

		$type         = 'post_' . $post->post_type;
		$trid         = apply_filters( 'wpml_element_trid', null, $post->ID, $type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		$translations = $trid ? apply_filters( 'wpml_get_element_translations', null, $trid, $type ) : null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API.
		if ( ! is_array( $translations ) || empty( $translations ) ) {
			return $xml;
		}

		foreach ( $translations as $code => $translation ) {
			if ( empty( $translation->element_id ) || ( isset( $translation->post_status ) && 'publish' !== $translation->post_status ) ) {
				continue;
			}
			$tid = (int) $translation->element_id;
			$robots = Post_Meta::get( $tid, Post_Meta::ROBOTS, 'index,follow' );
			if ( false !== strpos( (string) $robots, 'noindex' ) ) {
				continue;
			}
			$url = get_permalink( $tid );
			if ( ! $url ) {
				continue;
			}
			$xml .= '<xhtml:link rel="alternate" hreflang="' . esc_attr( (string) $code ) . '" href="' . esc_url( $url ) . '" />';
		}

		return $xml;
	}
}
