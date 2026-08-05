<?php
/**
 * Lightweight English word-form helpers for keyphrase matching.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Analysis;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates morphological variants and matches them in text.
 */
class Word_Forms {

	/**
	 * Does haystack contain the phrase or a word-form variant?
	 *
	 * Multi-word phrases match when every significant word (or a form of it)
	 * appears in haystack — order-independent, similar to search engines.
	 *
	 * @param string $haystack Text.
	 * @param string $phrase   Keyphrase.
	 * @return bool
	 */
	public static function matches( $haystack, $phrase ) {
		$haystack = strtolower( (string) $haystack );
		$phrase   = strtolower( trim( (string) $phrase ) );
		if ( '' === $phrase || '' === $haystack ) {
			return false;
		}
		if ( false !== strpos( $haystack, $phrase ) ) {
			return true;
		}

		$words = preg_split( '/\s+/', $phrase, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! $words ) {
			return false;
		}

		foreach ( $words as $word ) {
			if ( strlen( $word ) < 3 ) {
				continue;
			}
			if ( ! self::word_matches( $haystack, $word ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Match a single word or any generated form.
	 *
	 * @param string $haystack Text.
	 * @param string $word     Word.
	 * @return bool
	 */
	private static function word_matches( $haystack, $word ) {
		foreach ( self::forms( $word ) as $form ) {
			if ( strlen( $form ) < 3 ) {
				continue;
			}
			if ( preg_match( '/\b' . preg_quote( $form, '/' ) . '\b/u', $haystack ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate simple English variants for a token.
	 *
	 * @param string $word Word.
	 * @return string[]
	 */
	public static function forms( $word ) {
		$word  = strtolower( trim( $word ) );
		$forms = array( $word );
		$stem  = self::stem( $word );
		$forms[] = $stem;

		foreach ( array( '', 's', 'es', 'ed', 'ing', 'er', 'ers', 'ly', 'tion', 'ations', 'al', 'als' ) as $suffix ) {
			$forms[] = $stem . $suffix;
		}

		// Common irregular-ish swaps.
		if ( substr( $word, -3 ) === 'ies' ) {
			$forms[] = substr( $word, 0, -3 ) . 'y';
		}
		if ( substr( $word, -1 ) === 'y' ) {
			$forms[] = substr( $word, 0, -1 ) . 'ies';
		}

		return array_values( array_unique( array_filter( $forms ) ) );
	}

	/**
	 * Crude stem by stripping common suffixes.
	 *
	 * @param string $word Word.
	 * @return string
	 */
	private static function stem( $word ) {
		$rules = array(
			'/ational$/' => 'ate',
			'/tional$/'  => 'tion',
			'/ences$/'   => 'ence',
			'/ings$/'    => 'ing',
			'/ing$/'     => '',
			'/ies$/'     => 'y',
			'/ied$/'     => 'y',
			'/ed$/'      => '',
			'/es$/'      => '',
			'/s$/'       => '',
			'/ly$/'      => '',
			'/er$/'      => '',
			'/est$/'     => '',
		);
		foreach ( $rules as $pattern => $replace ) {
			$next = preg_replace( $pattern, $replace, $word );
			if ( null !== $next && $next !== $word && strlen( $next ) >= 3 ) {
				return $next;
			}
		}
		return $word;
	}
}
