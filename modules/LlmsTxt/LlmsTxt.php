<?php
/**
 * Serves an llms.txt file for AI discovery.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\LlmsTxt;

use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publishes /llms.txt from cornerstone + recent content.
 */
class LlmsTxt {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'rewrite' ) );
		add_action( 'template_redirect', array( $this, 'serve' ) );
	}

	/**
	 * Rewrite rule.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?seofyme_llms_txt=1', 'top' );
		add_rewrite_tag( '%seofyme_llms_txt%', '1' );
	}

	/**
	 * Output llms.txt when requested.
	 *
	 * @return void
	 */
	public function serve() {
		if ( ! get_query_var( 'seofyme_llms_txt' ) && ! $this->is_direct_request() ) {
			return;
		}
		if ( ! Options::get( 'llms_txt', true ) ) {
			status_header( 404 );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo $this->build(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file.
		exit;
	}

	/**
	 * Direct path fallback before rewrite flush.
	 *
	 * @return bool
	 */
	private function is_direct_request() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
		$path = wp_parse_url( home_url( $uri ), PHP_URL_PATH );
		$base = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$rel  = '/' === $base ? $path : preg_replace( '#^' . preg_quote( untrailingslashit( $base ), '#' ) . '#', '', (string) $path );
		return '/llms.txt' === $rel || 'llms.txt' === ltrim( (string) $rel, '/' );
	}

	/**
	 * Build file body.
	 *
	 * @return string
	 */
	public function build() {
		$name = Options::get( 'organization_name' ) ?: get_bloginfo( 'name' );
		$desc = get_bloginfo( 'description' );
		$out  = '# ' . $name . "\n";
		if ( $desc ) {
			$out .= '> ' . $desc . "\n";
		}
		$out .= "\n## Important pages\n\n";

		$stones = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				'meta_key'       => Post_Meta::CORNERSTONE,
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);
		if ( empty( $stones ) ) {
			$stones = get_posts(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => 25,
					'orderby'        => 'modified',
					'no_found_rows'  => true,
				)
			);
		}

		foreach ( $stones as $post ) {
			$url   = get_permalink( $post );
			$title = get_the_title( $post );
			$blurb = Post_Meta::resolved_description( $post->ID );
			if ( ! $blurb ) {
				$blurb = wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 );
			}
			$out .= '- [' . $title . '](' . $url . '): ' . $blurb . "\n";
		}

		$out .= "\n## Sitemap\n\n";
		$out .= '- [XML sitemap](' . home_url( '/sitemap.xml' ) . ")\n";

		/**
		 * Filter llms.txt body.
		 *
		 * @param string $out Body.
		 */
		return (string) apply_filters( 'seofyme_llms_txt', $out );
	}
}
