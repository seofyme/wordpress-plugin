<?php
/**
 * Headline analyzer.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Headline;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scores titles for CTR potential.
 */
class HeadlineAnalyzer {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_seofyme_headline_score', array( $this, 'ajax' ) );
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		add_meta_box( 'seofyme_headline', __( 'Headline analyzer', 'seofyme-seo' ), array( $this, 'render' ), 'post', 'side' );
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		$result = $this->score( get_the_title( $post ) );
		echo '<div id="seofyme-headline-box" data-post-id="' . esc_attr( (string) $post->ID ) . '">';
		echo '<p class="seofyme-score seofyme-score--' . esc_attr( $result['label'] ) . '">' . esc_html( sprintf( /* translators: %d */ __( 'Score: %d / 100', 'seofyme-seo' ), $result['score'] ) ) . '</p>';
		echo '<ul class="seofyme-suggestion-list">';
		foreach ( $result['tips'] as $tip ) {
			echo '<li>' . esc_html( $tip ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Score a headline.
	 *
	 * @param string $title Title.
	 * @return array
	 */
	public function score( $title ) {
		$title = trim( wp_strip_all_tags( (string) $title ) );
		$score = 40;
		$tips  = array();
		$len   = strlen( $title );
		$words = str_word_count( $title );

		if ( $len >= 40 && $len <= 60 ) {
			$score += 20;
			$tips[] = __( 'Length is in a strong CTR range.', 'seofyme-seo' );
		} else {
			$tips[] = __( 'Aim for roughly 40–60 characters.', 'seofyme-seo' );
		}

		if ( $words >= 5 && $words <= 12 ) {
			$score += 15;
		} else {
			$tips[] = __( 'Use about 5–12 words.', 'seofyme-seo' );
		}

		if ( preg_match( '/\d/', $title ) ) {
			$score += 10;
			$tips[] = __( 'Numbers in headlines often lift clicks.', 'seofyme-seo' );
		} else {
			$tips[] = __( 'Consider adding a number (e.g. “7 ways…”).', 'seofyme-seo' );
		}

		$power = array( 'how', 'why', 'best', 'guide', 'ultimate', 'complete', 'proven', 'simple', 'fast', 'new' );
		$lower = strtolower( $title );
		foreach ( $power as $word ) {
			if ( false !== strpos( $lower, $word ) ) {
				$score += 10;
				$tips[] = __( 'Power words detected.', 'seofyme-seo' );
				break;
			}
		}

		if ( preg_match( '/[!?:]/', $title ) ) {
			$score += 5;
		}

		$score = min( 100, $score );
		$label = $score >= 70 ? 'good' : ( $score >= 45 ? 'ok' : 'bad' );
		return compact( 'score', 'label', 'tips' );
	}

	/**
	 * AJAX.
	 *
	 * @return void
	 */
	public function ajax() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		wp_send_json_success( $this->score( $title ) );
	}
}
