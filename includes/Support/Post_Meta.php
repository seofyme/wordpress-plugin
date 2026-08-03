<?php
/**
 * Per-post SEO meta helpers (Seofyme-owned keys only).
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads/writes `_seofyme_*` post meta.
 */
class Post_Meta {

	public const TITLE       = '_seofyme_title';
	public const DESCRIPTION = '_seofyme_description';
	public const FOCUS_KW    = '_seofyme_focus_keyphrase';
	public const CANONICAL   = '_seofyme_canonical';
	public const ROBOTS      = '_seofyme_robots';
	public const CORNERSTONE = '_seofyme_cornerstone';
	public const KEYPHRASES  = '_seofyme_keyphrases';
	public const OG_TITLE    = '_seofyme_og_title';
	public const OG_DESC     = '_seofyme_og_description';
	public const OG_IMAGE    = '_seofyme_og_image';
	public const TW_TITLE    = '_seofyme_twitter_title';
	public const TW_DESC     = '_seofyme_twitter_description';
	public const TW_IMAGE    = '_seofyme_twitter_image';

	/**
	 * Get meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function get( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || false === $value ) ? $default : $value;
	}

	/**
	 * Update meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public static function set( $post_id, $key, $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Resolved SEO title for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function resolved_title( $post_id ) {
		$custom = self::get( $post_id, self::TITLE );
		if ( $custom ) {
			return self::replace_vars( $custom, $post_id );
		}
		$sep  = Options::get( 'title_separator', '|' );
		$name = get_bloginfo( 'name' );
		return trim( get_the_title( $post_id ) . ' ' . $sep . ' ' . $name );
	}

	/**
	 * Resolved meta description.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function resolved_description( $post_id ) {
		$custom = self::get( $post_id, self::DESCRIPTION );
		if ( $custom ) {
			return self::replace_vars( $custom, $post_id );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$excerpt = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		return wp_trim_words( wp_strip_all_tags( $excerpt ), 30, '' );
	}

	/**
	 * Simple template vars.
	 *
	 * @param string $text Text.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function replace_vars( $text, $post_id ) {
		$map = array(
			'%%title%%'        => get_the_title( $post_id ),
			'%%sitename%%'     => get_bloginfo( 'name' ),
			'%%sep%%'          => Options::get( 'title_separator', '|' ),
			'%%excerpt%%'      => wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 30, '' ),
			'%%focuskw%%'      => self::get( $post_id, self::FOCUS_KW ),
			'%%currentyear%%'  => gmdate( 'Y' ),
		);
		return strtr( $text, $map );
	}
}
