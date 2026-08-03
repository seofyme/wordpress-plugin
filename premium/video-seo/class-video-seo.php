<?php
/**
 * Video SEO — schema + video sitemap.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects videos and outputs VideoObject schema + sitemap.
 */
class Seofyme_Video_SEO {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'rewrite' ] );
		add_action( 'template_redirect', [ $this, 'render_sitemap' ] );
		add_action( 'wp_head', [ $this, 'output_schema' ], 6 );
		add_action( 'save_post', [ $this, 'detect_and_store' ], 30 );
		add_filter( 'wpseo_sitemap_index', [ $this, 'add_to_index' ] );
	}

	/**
	 * Rewrite for video sitemap.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^video-sitemap\.xml$', 'index.php?seofyme_video_sitemap=1', 'top' );
		add_rewrite_tag( '%seofyme_video_sitemap%', '1' );
	}

	/**
	 * Detect embeds on save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function detect_and_store( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		$content = get_post_field( 'post_content', $post_id );
		$videos  = [];
		if ( preg_match_all( '#https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,})#i', (string) $content, $m ) ) {
			foreach ( $m[1] as $id ) {
				$videos[] = [
					'provider' => 'youtube',
					'id'       => $id,
					'content'  => 'https://www.youtube.com/watch?v=' . $id,
					'thumbnail'=> 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg',
				];
			}
		}
		if ( preg_match_all( '#https?://(?:www\.)?vimeo\.com/(\d+)#i', (string) $content, $m2 ) ) {
			foreach ( $m2[1] as $id ) {
				$videos[] = [
					'provider' => 'vimeo',
					'id'       => $id,
					'content'  => 'https://vimeo.com/' . $id,
					'thumbnail'=> '',
				];
			}
		}
		if ( has_block( 'core/video', $content ) || has_block( 'core/embed', $content ) ) {
			if ( empty( $videos ) ) {
				$videos[] = [
					'provider' => 'self',
					'id'       => (string) $post_id,
					'content'  => get_permalink( $post_id ),
					'thumbnail'=> get_the_post_thumbnail_url( $post_id, 'large' ) ?: '',
				];
			}
		}
		update_post_meta( $post_id, '_seofyme_videos', $videos );
	}

	/**
	 * Schema.
	 *
	 * @return void
	 */
	public function output_schema() {
		if ( ! is_singular() ) {
			return;
		}
		$videos = get_post_meta( get_queried_object_id(), '_seofyme_videos', true );
		if ( empty( $videos ) || ! is_array( $videos ) ) {
			return;
		}
		foreach ( $videos as $video ) {
			$data = [
				'@context'     => 'https://schema.org',
				'@type'        => 'VideoObject',
				'name'         => get_the_title(),
				'description'  => wp_trim_words( wp_strip_all_tags( get_the_content() ), 40 ),
				'thumbnailUrl' => $video['thumbnail'] ?: get_the_post_thumbnail_url( null, 'large' ),
				'uploadDate'   => get_the_date( 'c' ),
				'contentUrl'   => $video['content'],
				'embedUrl'     => $video['content'],
			];
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	}

	/**
	 * Render XML sitemap.
	 *
	 * @return void
	 */
	public function render_sitemap() {
		if ( ! get_query_var( 'seofyme_video_sitemap' ) ) {
			return;
		}
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';
		$q = new WP_Query(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'meta_key'       => '_seofyme_videos',
				'no_found_rows'  => true,
			]
		);
		foreach ( $q->posts as $post ) {
			$videos = get_post_meta( $post->ID, '_seofyme_videos', true );
			if ( empty( $videos ) || ! is_array( $videos ) ) {
				continue;
			}
			echo '<url>';
			echo '<loc>' . esc_url( get_permalink( $post ) ) . '</loc>';
			foreach ( $videos as $video ) {
				echo '<video:video>';
				echo '<video:thumbnail_loc>' . esc_url( $video['thumbnail'] ?: '' ) . '</video:thumbnail_loc>';
				echo '<video:title>' . esc_html( get_the_title( $post ) ) . '</video:title>';
				echo '<video:description>' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) ) . '</video:description>';
				echo '<video:player_loc>' . esc_url( $video['content'] ) . '</video:player_loc>';
				echo '</video:video>';
			}
			echo '</url>';
		}
		echo '</urlset>';
		exit;
	}

	/**
	 * Add to Yoast sitemap index when available.
	 *
	 * @param string $xml Index XML fragment.
	 * @return string
	 */
	public function add_to_index( $xml ) {
		$loc     = home_url( '/video-sitemap.xml' );
		$lastmod = gmdate( 'c' );
		$xml    .= "<sitemap><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod></sitemap>\n";
		return $xml;
	}
}
