<?php
/**
 * Content ideas + starter drafts from site topics.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\ContentPlanner;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content planner metabox.
 */
class ContentPlanner {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'wp_ajax_seofyme_content_ideas', array( $this, 'ajax_ideas' ) );
		add_action( 'wp_ajax_seofyme_starter_draft', array( $this, 'ajax_draft' ) );
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		add_meta_box( 'seofyme_planner', __( 'Content planner', 'seofyme-seo' ), array( $this, 'render' ), 'post', 'side', 'high' );
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		echo '<p class="description">' . esc_html__( 'Ideas drawn from your site topics, plus a structured starter draft.', 'seofyme-seo' ) . '</p>';
		echo '<p><button type="button" class="button" id="seofyme-content-ideas">' . esc_html__( 'Suggest ideas', 'seofyme-seo' ) . '</button></p>';
		echo '<ul id="seofyme-idea-list"></ul>';
		echo '<p><button type="button" class="button button-primary" id="seofyme-starter-draft">' . esc_html__( 'Build starter draft', 'seofyme-seo' ) . '</button></p>';
	}

	/**
	 * Ideas.
	 *
	 * @return array
	 */
	public function ideas() {
		$posts  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 30, 'no_found_rows' => true ) );
		$topics = array();
		foreach ( $posts as $post ) {
			foreach ( wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ) as $cat ) {
				$topics[ $cat ] = ( $topics[ $cat ] ?? 0 ) + 1;
			}
			$focus = Post_Meta::get( $post->ID, Post_Meta::FOCUS_KW );
			if ( $focus ) {
				$topics[ $focus ] = ( $topics[ $focus ] ?? 0 ) + 1;
			}
		}
		arsort( $topics );
		$top = array_slice( array_keys( $topics ), 0, 5 );
		$top = array_slice( array_unique( array_merge( $top, array( 'Beginner guide', 'Common mistakes', 'Checklist', 'Comparison', 'Case study' ) ) ), 0, 5 );
		$ideas = array();
		foreach ( $top as $topic ) {
			$ideas[] = array(
				'title' => sprintf( 'The complete guide to %s', $topic ),
				'topic' => $topic,
			);
		}
		return $ideas;
	}

	/**
	 * Starter draft.
	 *
	 * @param string $topic Topic.
	 * @return string
	 */
	public function starter( $topic ) {
		$topic = sanitize_text_field( $topic );
		$t     = esc_html( $topic );
		return implode(
			"\n\n",
			array(
				"<!-- wp:heading --><h2>Introduction</h2><!-- /wp:heading -->",
				"<!-- wp:paragraph --><p>Introduce why {$t} matters.</p><!-- /wp:paragraph -->",
				"<!-- wp:heading --><h2>What is {$t}?</h2><!-- /wp:heading -->",
				"<!-- wp:paragraph --><p>Define the topic clearly.</p><!-- /wp:paragraph -->",
				"<!-- wp:heading --><h2>How to get started</h2><!-- /wp:heading -->",
				"<!-- wp:list --><ul><li>Step one</li><li>Step two</li><li>Step three</li></ul><!-- /wp:list -->",
				"<!-- wp:heading --><h2>Best practices</h2><!-- /wp:heading -->",
				"<!-- wp:paragraph --><p>Share practical tips.</p><!-- /wp:paragraph -->",
				"<!-- wp:heading --><h2>FAQ</h2><!-- /wp:heading -->",
				"<!-- wp:paragraph --><p>Answer real customer questions.</p><!-- /wp:paragraph -->",
				"<!-- wp:heading --><h2>Conclusion</h2><!-- /wp:heading -->",
				"<!-- wp:paragraph --><p>End with a clear next step.</p><!-- /wp:paragraph -->",
			)
		);
	}

	/**
	 * AJAX ideas.
	 *
	 * @return void
	 */
	public function ajax_ideas() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( null, 403 );
		}
		wp_send_json_success( array( 'ideas' => $this->ideas() ) );
	}

	/**
	 * AJAX draft.
	 *
	 * @return void
	 */
	public function ajax_draft() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( null, 403 );
		}
		$topic = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : 'this topic';
		wp_send_json_success( array( 'content' => $this->starter( $topic ) ) );
	}
}
