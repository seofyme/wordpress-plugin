<?php
/**
 * Content planner — topic ideas + starter draft structure.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggests content ideas from existing site topics.
 */
class Seofyme_Content_Planner {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'wp_ajax_seofyme_content_ideas', [ $this, 'ajax_ideas' ] );
		add_action( 'wp_ajax_seofyme_starter_draft', [ $this, 'ajax_draft' ] );
	}

	/**
	 * Meta box on new posts.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'seofyme_content_planner',
			__( 'Seofyme — Content planner', 'seofyme-seo' ),
			[ $this, 'render' ],
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Render.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		echo '<p class="description">' . esc_html__( 'Get five topic ideas drawn from your own site, then build a structured starter draft.', 'seofyme-seo' ) . '</p>';
		echo '<p><button type="button" class="button" id="seofyme-content-ideas">' . esc_html__( 'Suggest ideas', 'seofyme-seo' ) . '</button></p>';
		echo '<ul id="seofyme-idea-list"></ul>';
		echo '<p><button type="button" class="button button-primary" id="seofyme-starter-draft" data-post-id="' . esc_attr( (string) $post->ID ) . '">' . esc_html__( 'Build starter draft', 'seofyme-seo' ) . '</button></p>';
	}

	/**
	 * Ideas from site content.
	 *
	 * @return array
	 */
	public function ideas() {
		$posts = get_posts(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 30,
				'orderby'        => 'modified',
				'no_found_rows'  => true,
			]
		);

		$topics = [];
		foreach ( $posts as $post ) {
			$cats = wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] );
			foreach ( $cats as $cat ) {
				$topics[ $cat ] = ( $topics[ $cat ] ?? 0 ) + 1;
			}
			$focus = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
			if ( $focus ) {
				$topics[ $focus ] = ( $topics[ $focus ] ?? 0 ) + 1;
			}
		}
		arsort( $topics );
		$top = array_slice( array_keys( $topics ), 0, 5 );
		if ( count( $top ) < 5 ) {
			$top = array_merge(
				$top,
				[
					'Beginner guide',
					'Common mistakes',
					'Comparison',
					'Checklist',
					'Case study',
				]
			);
			$top = array_slice( array_unique( $top ), 0, 5 );
		}

		$ideas = [];
		foreach ( $top as $topic ) {
			$ideas[] = [
				'title' => sprintf( 'The complete guide to %s', $topic ),
				'topic' => $topic,
			];
		}
		return $ideas;
	}

	/**
	 * Starter outline HTML.
	 *
	 * @param string $topic Topic.
	 * @return string
	 */
	public function starter_draft( $topic ) {
		$topic = sanitize_text_field( $topic );
		$parts = [
			'<!-- wp:heading --><h2>Introduction</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Introduce why ' . esc_html( $topic ) . ' matters for your audience.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>What is ' . esc_html( $topic ) . '?</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Define the concept in plain language.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>How to get started</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul><li>Step one</li><li>Step two</li><li>Step three</li></ul><!-- /wp:list -->',
			'<!-- wp:heading --><h2>Best practices</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Share practical tips based on what already works on your site.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>FAQ</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Answer the questions your customers actually ask.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2>Conclusion</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Summarize the next action readers should take.</p><!-- /wp:paragraph -->',
		];
		return implode( "\n\n", $parts );
	}

	/**
	 * AJAX ideas.
	 *
	 * @return void
	 */
	public function ajax_ideas() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		wp_send_json_success( [ 'ideas' => $this->ideas() ] );
	}

	/**
	 * AJAX draft.
	 *
	 * @return void
	 */
	public function ajax_draft() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$topic = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : 'this topic';
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		wp_send_json_success( [ 'content' => $this->starter_draft( $topic ) ] );
	}
}
