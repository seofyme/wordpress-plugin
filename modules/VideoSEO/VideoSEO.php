<?php
/**
 * Video detection, schema, sitemap.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\VideoSEO;

use SeofymeSEO\Schema\Json_Ld;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Video SEO module.
 */
class VideoSEO {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'rewrite' ) );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
		add_action( 'save_post', array( $this, 'detect' ), 20 );
		add_action( 'wp_head', array( $this, 'schema' ), 6 );
	}

	/**
	 * Rewrite.
	 *
	 * @return void
	 */
	public function rewrite() {
		add_rewrite_rule( '^video-sitemap\.xml$', 'index.php?seofyme_video_sitemap=1', 'top' );
		add_rewrite_tag( '%seofyme_video_sitemap%', '1' );
	}

	/**
	 * Detect videos.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function detect( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		$content = (string) get_post_field( 'post_content', $post_id );
		$videos  = array();
		if ( preg_match_all( '#https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,})#i', $content, $m ) ) {
			foreach ( $m[1] as $id ) {
				$videos[] = array(
					'content'   => 'https://www.youtube.com/watch?v=' . $id,
					'thumbnail' => 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg',
				);
			}
		}
		update_post_meta( $post_id, '_seofyme_videos', $videos );
	}

	/**
	 * Schema.
	 *
	 * @return void
	 */
	public function schema() {
		if ( ! is_singular() ) {
			return;
		}
		$videos = get_post_meta( get_queried_object_id(), '_seofyme_videos', true );
		if ( empty( $videos ) || ! is_array( $videos ) ) {
			return;
		}
		foreach ( $videos as $video ) {
			$data = array(
				'@context'     => 'https://schema.org',
				'@type'        => 'VideoObject',
				'name'         => get_the_title(),
				'description'  => wp_trim_words( wp_strip_all_tags( get_the_content() ), 40 ),
				'thumbnailUrl' => $video['thumbnail'] ?? '',
				'uploadDate'   => get_the_date( 'c' ),
				'contentUrl'   => $video['content'] ?? '',
			);
			Json_Ld::print_script( $data );
		}
	}

	/**
	 * Sitemap.
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
		$q = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required meta lookup.
				'meta_query'     => array(
					array(
						'key'     => '_seofyme_videos',
						'compare' => 'EXISTS',
					),
				),
				'no_found_rows'  => true,
			)
		);
		foreach ( $q->posts as $post ) {
			$videos = get_post_meta( $post->ID, '_seofyme_videos', true );
			if ( empty( $videos ) || ! is_array( $videos ) ) {
				continue;
			}
			echo '<url><loc>' . esc_url( get_permalink( $post ) ) . '</loc>';
			foreach ( $videos as $video ) {
				echo '<video:video>';
				echo '<video:thumbnail_loc>' . esc_url( $video['thumbnail'] ?? '' ) . '</video:thumbnail_loc>';
				echo '<video:title>' . esc_html( get_the_title( $post ) ) . '</video:title>';
				echo '<video:description>' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) ) . '</video:description>';
				echo '<video:player_loc>' . esc_url( $video['content'] ?? '' ) . '</video:player_loc>';
				echo '</video:video>';
			}
			echo '</url>';
		}
		echo '</urlset>';
		exit;
	}
}
