<?php
/**
 * Orphaned content detection.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\InternalLinking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds posts with no inbound internal links.
 */
class OrphanedContent {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		// Used by workouts.
	}

	/**
	 * Find orphans.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function find( $limit = 50 ) {
		$posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
			)
		);
		$sources = get_posts(
			array(
				'post_type'      => get_post_types( array( 'public' => true ), 'names' ),
				'post_status'    => 'publish',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$linked = array();
		foreach ( $sources as $source_id ) {
			$content = (string) get_post_field( 'post_content', $source_id );
			if ( preg_match_all( '#href=["\']([^"\']+)["\']#i', $content, $m ) ) {
				foreach ( $m[1] as $href ) {
					$id = url_to_postid( $href );
					if ( $id && (int) $id !== (int) $source_id ) {
						$linked[ $id ] = true;
					}
				}
			}
		}

		$orphans = array();
		foreach ( $posts as $post ) {
			if ( isset( $linked[ $post->ID ] ) || (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
				continue;
			}
			$orphans[] = array(
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
			);
			if ( count( $orphans ) >= $limit ) {
				break;
			}
		}
		return $orphans;
	}
}
