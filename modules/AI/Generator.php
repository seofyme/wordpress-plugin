<?php
/**
 * AI title / meta drafting.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\AI;

use SeofymeSEO\Support\Options;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates draft SEO copy via LLM or offline heuristics.
 */
class Generator {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'wp_ajax_seofyme_ai_generate', array( $this, 'ajax' ) );
	}

	/**
	 * Box.
	 *
	 * @return void
	 */
	public function box() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			add_meta_box( 'seofyme_ai', __( 'AI draft', 'seofyme-seo' ), array( $this, 'render' ), $type, 'side', 'high' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		echo '<p class="description">' . esc_html__( 'Draft titles and meta descriptions. Nothing saves until you apply a suggestion.', 'seofyme-seo' ) . '</p>';
		echo '<p><button type="button" class="button button-primary" id="seofyme-ai-titles" data-post-id="' . esc_attr( (string) $post->ID ) . '">' . esc_html__( 'Generate titles', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button" id="seofyme-ai-metas" data-post-id="' . esc_attr( (string) $post->ID ) . '">' . esc_html__( 'Generate descriptions', 'seofyme-seo' ) . '</button></p>';
		echo '<div id="seofyme-ai-results"></div>';
	}

	/**
	 * Generate.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $kind Kind.
	 * @return array|\WP_Error
	 */
	public function generate( $post_id, $kind = 'titles' ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}
		$key = Options::get( 'ai_api_key', '' );
		if ( ! $key ) {
			return $this->fallback( $post, $kind );
		}
		$prompt = $this->prompt( $post, $kind );
		return 'anthropic' === Options::get( 'ai_provider' ) ? $this->anthropic( $key, $prompt ) : $this->openai( $key, $prompt );
	}

	/**
	 * Prompt.
	 *
	 * @param \WP_Post $post Post.
	 * @param string   $kind Kind.
	 * @return string
	 */
	private function prompt( $post, $kind ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 120 );
		$focus   = Post_Meta::get( $post->ID, Post_Meta::FOCUS_KW );
		if ( 'metas' === $kind ) {
			return "Write 5 SEO meta descriptions (max 155 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}. Return JSON array of strings only.";
		}
		return "Write 5 SEO titles (max 60 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}. Return JSON array of strings only.";
	}

	/**
	 * Offline fallback.
	 *
	 * @param \WP_Post $post Post.
	 * @param string   $kind Kind.
	 * @return array
	 */
	private function fallback( $post, $kind ) {
		$focus = Post_Meta::get( $post->ID, Post_Meta::FOCUS_KW );
		$base  = $post->post_title;
		if ( 'metas' === $kind ) {
			$snip = wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 );
			return array(
				wp_html_excerpt( ( $focus ? $focus . ': ' : '' ) . $snip, 155 ),
				wp_html_excerpt( 'Learn about ' . $base . '. ' . $snip, 155 ),
				wp_html_excerpt( $base . ' — practical guide. ' . $snip, 155 ),
				wp_html_excerpt( 'Discover ' . $base . '. ' . $snip, 155 ),
				wp_html_excerpt( $snip, 155 ),
			);
		}
		return array(
			wp_html_excerpt( $base . ( $focus ? " ({$focus})" : '' ), 60 ),
			wp_html_excerpt( $base . ' | Complete Guide', 60 ),
			wp_html_excerpt( ( $focus ?: $base ) . ' Explained', 60 ),
			wp_html_excerpt( 'How to ' . $base, 60 ),
			wp_html_excerpt( $base . ' — Tips & Best Practices', 60 ),
		);
	}

	/**
	 * OpenAI.
	 *
	 * @param string $key Key.
	 * @param string $prompt Prompt.
	 * @return array|\WP_Error
	 */
	private function openai( $key, $prompt ) {
		$res = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'    => 'gpt-4o-mini',
						'messages' => array(
							array( 'role' => 'system', 'content' => 'SEO assistant. Reply with JSON arrays of strings only.' ),
							array( 'role' => 'user', 'content' => $prompt ),
						),
					)
				),
			)
		);
		return $this->parse( $res );
	}

	/**
	 * Anthropic.
	 *
	 * @param string $key Key.
	 * @param string $prompt Prompt.
	 * @return array|\WP_Error
	 */
	private function anthropic( $key, $prompt ) {
		$res = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 45,
				'headers' => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => 'claude-3-5-haiku-latest',
						'max_tokens' => 800,
						'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
					)
				),
			)
		);
		return $this->parse( $res );
	}

	/**
	 * Parse LLM JSON.
	 *
	 * @param array|\WP_Error $res Response.
	 * @return array|\WP_Error
	 */
	private function parse( $res ) {
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		$text = $body['choices'][0]['message']['content'] ?? ( $body['content'][0]['text'] ?? '' );
		$text = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', trim( (string) $text ) );
		$data = json_decode( $text, true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'parse_error', 'Could not parse AI response' );
		}
		return array_values( array_map( 'sanitize_text_field', $data ) );
	}

	/**
	 * AJAX.
	 *
	 * @return void
	 */
	public function ajax() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$kind    = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'titles';
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		$result = $this->generate( $post_id, $kind );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'items' => $result, 'kind' => $kind ) );
	}
}
