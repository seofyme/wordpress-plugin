<?php
/**
 * AI training bot blocker via robots.txt.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks selected AI crawlers.
 */
class Seofyme_Bot_Blocker {

	/**
	 * Known bots.
	 *
	 * @return array
	 */
	public static function known_bots() {
		return [
			'GPTBot'           => 'GPTBot (OpenAI)',
			'ChatGPT-User'     => 'ChatGPT-User',
			'CCBot'            => 'CCBot (Common Crawl)',
			'Google-Extended'  => 'Google-Extended (Gemini training)',
			'anthropic-ai'     => 'anthropic-ai',
			'ClaudeBot'        => 'ClaudeBot',
			'Bytespider'       => 'Bytespider',
			'cohere-ai'        => 'cohere-ai',
			'Diffbot'          => 'Diffbot',
			'FacebookBot'      => 'FacebookBot',
		];
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'robots_txt', [ $this, 'filter_robots' ], 20, 2 );
	}

	/**
	 * Append Disallow rules.
	 *
	 * @param string $output Robots txt.
	 * @param bool   $public Blog public.
	 * @return string
	 */
	public function filter_robots( $output, $public ) {
		$blocked = (array) get_option( 'seofyme_bot_blocker', [] );
		if ( empty( $blocked ) ) {
			return $output;
		}

		$extra = "\n# Seofyme SEO — AI bot blocker\n";
		foreach ( $blocked as $bot ) {
			$bot = sanitize_text_field( $bot );
			if ( $bot === '' ) {
				continue;
			}
			$extra .= "User-agent: {$bot}\nDisallow: /\n\n";
		}

		return $output . $extra;
	}
}
