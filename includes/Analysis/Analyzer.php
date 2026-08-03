<?php
/**
 * On-page SEO + readability checks.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Analysis;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule-based content analyzer (Seofyme strategy).
 */
class Analyzer {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'rest' ) );
	}

	/**
	 * Analyze a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function analyze_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'score'  => 0,
				'label'  => 'bad',
				'checks' => array(),
			);
		}

		$content   = wp_strip_all_tags( $post->post_content );
		$title     = Post_Meta::resolved_title( $post_id );
		$desc      = Post_Meta::resolved_description( $post_id );
		$focus     = strtolower( Post_Meta::get( $post_id, Post_Meta::FOCUS_KW ) );
		$words     = str_word_count( $content );
		$checks    = array();
		$score     = 0;

		// Focus keyphrase present.
		if ( $focus ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Focus keyphrase is set.', 'seofyme-seo' ) );
			$score   += 15;
		} else {
			$checks[] = array( 'status' => 'bad', 'message' => __( 'Add a focus keyphrase.', 'seofyme-seo' ) );
		}

		// Keyphrase in title.
		if ( $focus && false !== strpos( strtolower( $title ), $focus ) ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Focus keyphrase appears in the SEO title.', 'seofyme-seo' ) );
			$score   += 15;
		} elseif ( $focus ) {
			$checks[] = array( 'status' => 'ok', 'message' => __( 'Consider adding the focus keyphrase to the SEO title.', 'seofyme-seo' ) );
		}

		// Keyphrase in content.
		if ( $focus && false !== strpos( strtolower( $content ), $focus ) ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Focus keyphrase appears in the content.', 'seofyme-seo' ) );
			$score   += 15;
		} elseif ( $focus ) {
			$checks[] = array( 'status' => 'bad', 'message' => __( 'Focus keyphrase was not found in the content.', 'seofyme-seo' ) );
		}

		// Title length.
		$tlen = strlen( $title );
		if ( $tlen >= 30 && $tlen <= 60 ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'SEO title length looks good.', 'seofyme-seo' ) );
			$score   += 10;
		} else {
			$checks[] = array( 'status' => 'ok', 'message' => __( 'SEO title should usually be 30–60 characters.', 'seofyme-seo' ) );
		}

		// Description length.
		$dlen = strlen( $desc );
		if ( $dlen >= 70 && $dlen <= 160 ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Meta description length looks good.', 'seofyme-seo' ) );
			$score   += 10;
		} else {
			$checks[] = array( 'status' => 'ok', 'message' => __( 'Meta description should usually be 70–160 characters.', 'seofyme-seo' ) );
		}

		// Content length.
		if ( $words >= 300 ) {
			$checks[] = array( 'status' => 'good', 'message' => sprintf( /* translators: %d words */ __( 'Content length is solid (%d words).', 'seofyme-seo' ), $words ) );
			$score   += 15;
		} else {
			$checks[] = array( 'status' => 'ok', 'message' => sprintf( /* translators: %d words */ __( 'Content is short (%d words). Aim for 300+ for competitive topics.', 'seofyme-seo' ), $words ) );
		}

		// Readability: sentence length approx.
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$sentence_count = max( 1, count( $sentences ) );
		$avg = $words / $sentence_count;
		if ( $avg <= 20 ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Average sentence length is easy to read.', 'seofyme-seo' ) );
			$score   += 10;
		} else {
			$checks[] = array( 'status' => 'ok', 'message' => __( 'Sentences are a bit long — shorter sentences improve readability.', 'seofyme-seo' ) );
		}

		// Headings.
		if ( preg_match( '/<h2[\s>]/i', $post->post_content ) ) {
			$checks[] = array( 'status' => 'good', 'message' => __( 'Content uses H2 subheadings.', 'seofyme-seo' ) );
			$score   += 10;
		} else {
			$checks[] = array( 'status' => 'ok', 'message' => __( 'Add H2 subheadings to structure the page.', 'seofyme-seo' ) );
		}

		$score = min( 100, $score );
		$label = $score >= 70 ? 'good' : ( $score >= 40 ? 'ok' : 'bad' );

		return compact( 'score', 'label', 'checks' );
	}

	/**
	 * REST.
	 *
	 * @return void
	 */
	public function rest() {
		register_rest_route(
			'seofyme/v1',
			'/analyze/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => static function ( $req ) {
					return current_user_can( 'edit_post', (int) $req['id'] );
				},
				'callback'            => function ( $req ) {
					return rest_ensure_response( $this->analyze_post( (int) $req['id'] ) );
				},
			)
		);
	}
}
