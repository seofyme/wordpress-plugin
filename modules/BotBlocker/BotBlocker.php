<?php
/**
 * AI training bot blocker.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\BotBlocker;

use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appends Disallow rules for selected AI crawlers.
 */
class BotBlocker {

	/**
	 * Known bots.
	 *
	 * @return array
	 */
	public static function known_bots() {
		return array(
			'GPTBot'          => 'GPTBot (OpenAI)',
			'ChatGPT-User'    => 'ChatGPT-User',
			'CCBot'           => 'CCBot',
			'Google-Extended' => 'Google-Extended',
			'anthropic-ai'    => 'anthropic-ai',
			'ClaudeBot'       => 'ClaudeBot',
			'Bytespider'      => 'Bytespider',
			'cohere-ai'       => 'cohere-ai',
		);
	}

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'robots_txt', array( $this, 'filter' ), 20, 2 );
	}

	/**
	 * Filter.
	 *
	 * @param string $output Output.
	 * @param bool   $public Public.
	 * @return string
	 */
	public function filter( $output, $public ) {
		$blocked = (array) Options::get( 'bot_blocker', array() );
		if ( empty( $blocked ) ) {
			return $output;
		}
		$extra = "\n# Seofyme SEO — AI bot blocker\n";
		foreach ( $blocked as $bot ) {
			$bot = sanitize_text_field( $bot );
			if ( $bot ) {
				$extra .= "User-agent: {$bot}\nDisallow: /\n\n";
			}
		}
		return $output . $extra;
	}
}
