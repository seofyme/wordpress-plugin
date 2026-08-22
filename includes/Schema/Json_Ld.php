<?php
/**
 * Safe JSON-LD encoding for HTML script tags.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encodes schema graphs without disabling JSON escaping.
 */
class Json_Ld {

	/**
	 * Flags that keep <, >, &, quotes escaped in JSON string values.
	 *
	 * JSON_UNESCAPED_SLASHES is intentionally omitted so a "</script>"
	 * sequence cannot break out of an HTML script element.
	 *
	 * @var int
	 */
	public const FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

	/**
	 * Encode data as JSON for embedding or download.
	 *
	 * @param mixed $data Data.
	 * @param int   $flags Extra json_encode flags (e.g. JSON_PRETTY_PRINT).
	 * @return string
	 */
	public static function encode( $data, $flags = 0 ) {
		$json = wp_json_encode( $data, self::FLAGS | (int) $flags );
		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Print a JSON-LD script tag.
	 *
	 * @param mixed $data Data.
	 * @return void
	 */
	public static function print_script( $data ) {
		if ( empty( $data ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Encoded with JSON_HEX_TAG and related hex flags.
		echo '<script type="application/ld+json">' . self::encode( $data ) . '</script>' . "\n";
	}
}
