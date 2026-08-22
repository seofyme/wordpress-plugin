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
		$id = (string) $post->ID;
		echo '<p class="description">' . esc_html__( 'Draft titles, metas, social copy, or optimization tips. Nothing saves until you click a suggestion.', 'seofyme-seo' ) . '</p>';
		echo '<p><button type="button" class="button button-primary seofyme-ai-btn" data-kind="titles" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Generate titles', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button seofyme-ai-btn" data-kind="metas" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Generate descriptions', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button seofyme-ai-btn" data-kind="social_titles" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Social titles', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button seofyme-ai-btn" data-kind="social_metas" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Social descriptions', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button seofyme-ai-btn" data-kind="optimize" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Optimize keyphrase tips', 'seofyme-seo' ) . '</button></p>';
		echo '<p><button type="button" class="button seofyme-ai-btn" data-kind="summarize" data-post-id="' . esc_attr( $id ) . '">' . esc_html__( 'Summarize', 'seofyme-seo' ) . '</button></p>';
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

		// Prefer Seofyme Cloud (product-scoped API — anti-null monetization path).
		$cloud = $this->seofyme_cloud( $post, $kind );
		if ( ! is_wp_error( $cloud ) || 'no_cloud_keys' !== $cloud->get_error_code() ) {
			return $cloud;
		}

		$prompt = $this->prompt( $post, $kind );

		$wp_ai = $this->wordpress_ai_client( $prompt );
		if ( ! is_wp_error( $wp_ai ) || 'no_wp_ai' !== $wp_ai->get_error_code() ) {
			return $wp_ai;
		}

		$key = Options::get( 'ai_api_key', '' );
		if ( ! $key ) {
			return $this->fallback( $post, $kind );
		}
		return 'anthropic' === Options::get( 'ai_provider' ) ? $this->anthropic( $key, $prompt ) : $this->openai( $key, $prompt );
	}

	/**
	 * Call Seofyme AI service via CacheRocket gateway.
	 *
	 * @param \WP_Post $post Post.
	 * @param string   $kind Kind.
	 * @return array|\WP_Error
	 */
	private function seofyme_cloud( $post, $kind ) {
		$public = (string) Options::get( 'seofyme_public_key', '' );
		$secret = (string) Options::get( 'seofyme_secret_key', '' );
		if ( '' === $public || '' === $secret ) {
			return new \WP_Error( 'no_cloud_keys', 'Seofyme Cloud keys not configured' );
		}

		$url = Options::cloud_api_base() . '/generate';

		$res = wp_remote_post(
			$url,
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'X-Product'     => 'seofyme',
					'X-Public-Key'  => $public,
					'X-Secret-Key'  => $secret,
				),
				'body'    => wp_json_encode(
					array(
						'kind'    => $kind,
						'title'   => get_the_title( $post ),
						'content' => wp_strip_all_tags( $post->post_content ),
						'focus'   => Post_Meta::get( $post->ID, Post_Meta::FOCUS_KW ),
					)
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'parse_error', __( 'Invalid response from Seofyme Cloud.', 'seofyme-seo' ) );
		}
		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $body['message'] ) ? (string) $body['message'] : __( 'Seofyme Cloud request failed.', 'seofyme-seo' );
			return new \WP_Error( 'api_error', $msg );
		}
		$items = isset( $body['data']['items'] ) && is_array( $body['data']['items'] ) ? $body['data']['items'] : array();
		$items = array_values(
			array_filter(
				array_map( 'sanitize_text_field', array_map( 'strval', $items ) ),
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
		if ( empty( $items ) ) {
			return new \WP_Error( 'parse_error', __( 'Seofyme Cloud returned no suggestions.', 'seofyme-seo' ) );
		}
		return $items;
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
		$format  = ' Reply with JSON only in this exact shape: {"items":["string1","string2",...]} — no markdown.';
		if ( 'metas' === $kind ) {
			return "Write 5 SEO meta descriptions (max 155 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}." . $format;
		}
		if ( 'social_titles' === $kind ) {
			return "Write 5 engaging social share titles for Facebook/X (max 70 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}." . $format;
		}
		if ( 'social_metas' === $kind ) {
			return "Write 5 social share descriptions for Facebook/X (max 160 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}." . $format;
		}
		if ( 'summarize' === $kind ) {
			return "Summarize this post in 3 short bullet points for a brief/social post. Title: {$post->post_title}. Content: {$excerpt}." . $format;
		}
		if ( 'optimize' === $kind ) {
			return "Give 5 concrete edits to improve keyphrase placement/density for \"{$focus}\" without keyword stuffing. Title: {$post->post_title}. Content: {$excerpt}." . $format;
		}
		return "Write 5 SEO titles (max 60 chars). Title: {$post->post_title}. Focus: {$focus}. Content: {$excerpt}." . $format;
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
		if ( 'metas' === $kind || 'social_metas' === $kind ) {
			$snip = wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 );
			$max  = 'social_metas' === $kind ? 160 : 155;
			return array(
				wp_html_excerpt( ( $focus ? $focus . ': ' : '' ) . $snip, $max ),
				wp_html_excerpt( 'Learn about ' . $base . '. ' . $snip, $max ),
				wp_html_excerpt( $base . ' — practical guide. ' . $snip, $max ),
				wp_html_excerpt( 'Discover ' . $base . '. ' . $snip, $max ),
				wp_html_excerpt( $snip, $max ),
			);
		}
		if ( 'social_titles' === $kind ) {
			return array(
				wp_html_excerpt( $base, 70 ),
				wp_html_excerpt( ( $focus ?: $base ) . ' — worth reading', 70 ),
				wp_html_excerpt( 'New: ' . $base, 70 ),
				wp_html_excerpt( $base . ' explained', 70 ),
				wp_html_excerpt( 'Why ' . $base . ' matters', 70 ),
			);
		}
		if ( 'summarize' === $kind ) {
			return array(
				'Overview of ' . $base,
				'Key takeaways around ' . ( $focus ?: $base ),
				'Next step: apply the ideas from this article.',
			);
		}
		if ( 'optimize' === $kind ) {
			$kw = $focus ?: 'your focus keyphrase';
			return array(
				'Add “' . $kw . '” near the start of the introduction.',
				'Use “' . $kw . '” in at least one H2 subheading.',
				'Keep density natural — roughly 0.5–2.5% of words.',
				'Include a synonym of “' . $kw . '” in the conclusion.',
				'Ensure the SEO title contains “' . $kw . '”.',
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
	 * Generate via the WordPress AI Client when a site-level provider is configured.
	 *
	 * @param string $prompt Prompt.
	 * @return array|\WP_Error
	 */
	private function wordpress_ai_client( $prompt ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new \WP_Error( 'no_wp_ai', 'WordPress AI Client not available' );
		}

		$builder = wp_ai_client_prompt( $prompt );
		if ( ! is_object( $builder ) ) {
			return new \WP_Error( 'no_wp_ai', 'WordPress AI Client not available' );
		}

		if ( method_exists( $builder, 'using_system_instruction' ) ) {
			$builder = $builder->using_system_instruction( 'You are an SEO assistant. Always reply with a JSON object: {"items":["..."]} where items is an array of strings. No markdown.' );
		}

		if ( method_exists( $builder, 'is_supported_for_text_generation' ) && ! $builder->is_supported_for_text_generation() ) {
			return new \WP_Error( 'no_wp_ai', 'No AI provider configured in WordPress' );
		}

		if ( method_exists( $builder, 'as_json_response' ) ) {
			$builder = $builder->as_json_response(
				array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'items' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'             => array( 'items' ),
				)
			);
		}

		if ( ! method_exists( $builder, 'generate_text' ) ) {
			return new \WP_Error( 'no_wp_ai', 'WordPress AI Client not available' );
		}

		$text = $builder->generate_text();
		if ( is_wp_error( $text ) ) {
			$message = strtolower( $text->get_error_message() );
			if ( false !== strpos( $message, 'no provider' ) || false !== strpos( $message, 'not configured' ) || false !== strpos( $message, 'no model' ) ) {
				return new \WP_Error( 'no_wp_ai', $text->get_error_message() );
			}
			return $text;
		}

		$items = $this->extract_items( (string) $text );
		if ( empty( $items ) ) {
			return new \WP_Error( 'parse_error', __( 'Could not parse AI response', 'seofyme-seo' ) );
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $items ),
				static function ( $item ) {
					return '' !== $item;
				}
			)
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
						'model'           => 'gpt-4o-mini',
						'response_format' => array( 'type' => 'json_object' ),
						'messages'        => array(
							array(
								'role'    => 'system',
								'content' => 'You are an SEO assistant. Always reply with a JSON object: {"items":["..."]} where items is an array of strings. No markdown.',
							),
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
						'system'     => 'You are an SEO assistant. Always reply with a JSON object: {"items":["..."]} where items is an array of strings. No markdown.',
						'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
					)
				),
			)
		);
		return $this->parse( $res );
	}

	/**
	 * Parse LLM JSON into a list of suggestion strings.
	 *
	 * @param array|\WP_Error $res Response.
	 * @return array|\WP_Error
	 */
	private function parse( $res ) {
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$body = json_decode( $raw, true );

		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'parse_error', __( 'Invalid response from AI provider.', 'seofyme-seo' ) );
		}

		if ( ! empty( $body['error']['message'] ) ) {
			return new \WP_Error( 'api_error', (string) $body['error']['message'] );
		}

		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'AI provider returned HTTP %d.', 'seofyme-seo' ),
					$code
				)
			);
		}

		$text = $body['choices'][0]['message']['content'] ?? ( $body['content'][0]['text'] ?? '' );
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return new \WP_Error( 'parse_error', __( 'Empty AI response.', 'seofyme-seo' ) );
		}

		$items = $this->extract_items( $text );
		if ( empty( $items ) ) {
			return new \WP_Error( 'parse_error', __( 'Could not parse AI response', 'seofyme-seo' ) );
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $items ),
				static function ( $item ) {
					return '' !== $item;
				}
			)
		);
	}

	/**
	 * Pull string suggestions from free-form model output.
	 *
	 * @param string $text Model text.
	 * @return string[]
	 */
	private function extract_items( $text ) {
		$text = preg_replace( '/^```(?:json)?\s*/i', '', $text );
		$text = preg_replace( '/\s*```$/', '', (string) $text );
		$text = trim( (string) $text );

		$data = json_decode( $text, true );
		if ( null === $data ) {
			if ( preg_match( '/\{.*\}/s', $text, $m ) ) {
				$data = json_decode( $m[0], true );
			}
			if ( null === $data && preg_match( '/\[.*\]/s', $text, $m ) ) {
				$data = json_decode( $m[0], true );
			}
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		// Bare list: ["a","b"].
		if ( $this->is_list( $data ) ) {
			return array_map( 'strval', $data );
		}

		// Preferred shape: {"items":[...]}.
		foreach ( array( 'items', 'suggestions', 'titles', 'descriptions', 'tips', 'summary', 'results' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
				return array_map( 'strval', array_values( $data[ $key ] ) );
			}
		}

		// First nested string list, e.g. {"seo_titles":["..."]}.
		foreach ( $data as $value ) {
			if ( is_array( $value ) && $this->is_list( $value ) ) {
				$strings = array_filter( $value, 'is_string' );
				if ( count( $strings ) === count( $value ) && ! empty( $strings ) ) {
					return array_map( 'strval', $strings );
				}
			}
		}

		return array();
	}

	/**
	 * List check compatible with PHP 7.4 / WP 6.0 (no array_is_list).
	 *
	 * @param array $arr Array.
	 * @return bool
	 */
	private function is_list( array $arr ) {
		if ( array() === $arr ) {
			return true;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
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
