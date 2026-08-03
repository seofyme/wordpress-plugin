<?php
/**
 * AI title / meta description drafting.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates SEO titles and meta descriptions via configured LLM provider.
 */
class Seofyme_AI_Generator {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_seofyme_ai_generate', [ $this, 'ajax_generate' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
	}

	/**
	 * Meta box actions.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $type ) {
			add_meta_box(
				'seofyme_ai',
				__( 'Seofyme — AI draft', 'seofyme-seo' ),
				[ $this, 'render_meta_box' ],
				$type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render controls.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		echo '<p class="description">' . esc_html__( 'Draft SEO titles and meta descriptions. Nothing saves until you approve.', 'seofyme-seo' ) . '</p>';
		echo '<p><button type="button" class="button button-primary" id="seofyme-ai-titles" data-post-id="' . esc_attr( (string) $post->ID ) . '">' . esc_html__( 'Generate titles', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button" id="seofyme-ai-metas" data-post-id="' . esc_attr( (string) $post->ID ) . '">' . esc_html__( 'Generate meta descriptions', 'seofyme-seo' ) . '</button></p>';
		echo '<div id="seofyme-ai-results"></div>';
	}

	/**
	 * Generate variants.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $kind titles|metas|summarize.
	 * @return array|WP_Error
	 */
	public function generate( $post_id, $kind = 'titles' ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', 'Post not found' );
		}

		$api_key = get_option( 'seofyme_ai_api_key', '' );
		if ( ! $api_key ) {
			// Deterministic offline fallback so the feature works without a key.
			return $this->fallback_generate( $post, $kind );
		}

		$provider = get_option( 'seofyme_ai_provider', 'openai' );
		$prompt   = $this->build_prompt( $post, $kind );

		if ( $provider === 'anthropic' ) {
			return $this->call_anthropic( $api_key, $prompt, $kind );
		}
		return $this->call_openai( $api_key, $prompt, $kind );
	}

	/**
	 * Build prompt.
	 *
	 * @param WP_Post $post Post.
	 * @param string  $kind Kind.
	 * @return string
	 */
	private function build_prompt( $post, $kind ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 120 );
		$focus   = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
		if ( $kind === 'metas' ) {
			return "Write 5 compelling SEO meta descriptions (max 155 chars each) for this page.\nTitle: {$post->post_title}\nFocus keyphrase: {$focus}\nContent: {$excerpt}\nReturn a JSON array of strings only.";
		}
		if ( $kind === 'summarize' ) {
			return "Summarize this post in 3 bullet points for a social brief.\nTitle: {$post->post_title}\nContent: {$excerpt}\nReturn a JSON array of strings only.";
		}
		return "Write 5 SEO-friendly title tags (max 60 chars) for this page.\nCurrent title: {$post->post_title}\nFocus keyphrase: {$focus}\nContent: {$excerpt}\nReturn a JSON array of strings only.";
	}

	/**
	 * Offline fallback generator.
	 *
	 * @param WP_Post $post Post.
	 * @param string  $kind Kind.
	 * @return array
	 */
	private function fallback_generate( $post, $kind ) {
		$focus = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
		$site  = wp_parse_url( home_url(), PHP_URL_HOST );
		$base  = $post->post_title;
		if ( $kind === 'metas' ) {
			$snippet = wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 );
			return [
				wp_html_excerpt( ( $focus ? $focus . ': ' : '' ) . $snippet, 155 ),
				wp_html_excerpt( 'Learn about ' . $base . '. ' . $snippet, 155 ),
				wp_html_excerpt( $base . ' — practical guide. ' . $snippet, 155 ),
				wp_html_excerpt( 'Discover ' . $base . ' tips and insights. ' . $snippet, 155 ),
				wp_html_excerpt( $snippet . ' Read more on ' . $site . '.', 155 ),
			];
		}
		if ( $kind === 'summarize' ) {
			return [
				'Overview of ' . $base,
				'Key takeaways for readers interested in ' . ( $focus ?: $base ),
				'Next step: apply the ideas from this article on your site.',
			];
		}
		$kw = $focus ? " ({$focus})" : '';
		return [
			wp_html_excerpt( $base . $kw, 60 ),
			wp_html_excerpt( $base . ' | Complete Guide', 60 ),
			wp_html_excerpt( ( $focus ?: $base ) . ' Explained', 60 ),
			wp_html_excerpt( 'How to ' . $base, 60 ),
			wp_html_excerpt( $base . ' — Tips & Best Practices', 60 ),
		];
	}

	/**
	 * OpenAI call.
	 *
	 * @param string $api_key Key.
	 * @param string $prompt Prompt.
	 * @param string $kind Kind.
	 * @return array|WP_Error
	 */
	private function call_openai( $api_key, $prompt, $kind ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			[
				'timeout' => 45,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'model'       => 'gpt-4o-mini',
						'messages'    => [
							[ 'role' => 'system', 'content' => 'You are an SEO assistant. Reply with JSON arrays of strings only.' ],
							[ 'role' => 'user', 'content' => $prompt ],
						],
						'temperature' => 0.7,
					]
				),
			]
		);
		return $this->parse_llm_response( $response, $kind );
	}

	/**
	 * Anthropic call.
	 *
	 * @param string $api_key Key.
	 * @param string $prompt Prompt.
	 * @param string $kind Kind.
	 * @return array|WP_Error
	 */
	private function call_anthropic( $api_key, $prompt, $kind ) {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			[
				'timeout' => 45,
				'headers' => [
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'model'      => 'claude-3-5-haiku-latest',
						'max_tokens' => 800,
						'messages'   => [
							[ 'role' => 'user', 'content' => $prompt ],
						],
					]
				),
			]
		);
		return $this->parse_llm_response( $response, $kind );
	}

	/**
	 * Parse provider response.
	 *
	 * @param array|WP_Error $response Response.
	 * @param string         $kind Kind.
	 * @return array|WP_Error
	 */
	private function parse_llm_response( $response, $kind ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code >= 400 ) {
			return new WP_Error( 'ai_error', 'AI provider error', [ 'status' => $code, 'body' => $body ] );
		}
		$text = $body['choices'][0]['message']['content'] ?? ( $body['content'][0]['text'] ?? '' );
		$text = trim( (string) $text );
		$text = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $text );
		$data = json_decode( $text, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'parse_error', 'Could not parse AI response', [ 'raw' => $text ] );
		}
		return array_values( array_map( 'sanitize_text_field', $data ) );
	}

	/**
	 * AJAX.
	 *
	 * @return void
	 */
	public function ajax_generate() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$kind    = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'titles';
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		$result = $this->generate( $post_id, $kind );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		wp_send_json_success( [ 'items' => $result, 'kind' => $kind ] );
	}

	/**
	 * REST.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/ai/generate',
			[
				'methods'             => 'POST',
				'permission_callback' => static function ( $req ) {
					return current_user_can( 'edit_post', (int) $req->get_param( 'post_id' ) );
				},
				'callback'            => function ( $req ) {
					$result = $this->generate( (int) $req['post_id'], sanitize_key( (string) $req['kind'] ) );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
					return rest_ensure_response( [ 'items' => $result ] );
				},
				'args'                => [
					'post_id' => [ 'required' => true, 'type' => 'integer' ],
					'kind'    => [ 'required' => false, 'type' => 'string', 'default' => 'titles' ],
				],
			]
		);
	}
}
