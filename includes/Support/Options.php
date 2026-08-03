<?php
/**
 * Plugin options.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide Seofyme settings.
 */
class Options {

	public const OPTION_KEY = 'seofyme_seo_options';

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'title_separator'     => '|',
			'homepage_title'      => '',
			'homepage_description'=> '',
			'noindex_search'      => true,
			'noindex_author'      => false,
			'xml_sitemap'         => true,
			'schema_enabled'      => true,
			'breadcrumbs'         => true,
			'ai_provider'         => 'openai',
			'ai_api_key'          => '',
			'bot_blocker'         => array(),
			'indexnow_key'        => '',
			'schema_aggregate'    => true,
			'organization_name'   => '',
			'organization_logo'   => '',
		);
	}

	/**
	 * Ensure option exists.
	 *
	 * @return void
	 */
	public static function ensure_defaults() {
		$current = get_option( self::OPTION_KEY, null );
		if ( null === $current ) {
			add_option( self::OPTION_KEY, self::defaults(), '', false );
		}
	}

	/**
	 * Get all options merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Get one option.
	 *
	 * @param string $key Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Update options (partial).
	 *
	 * @param array $values Values.
	 * @return void
	 */
	public static function update( array $values ) {
		$merged = array_merge( self::all(), $values );
		update_option( self::OPTION_KEY, $merged, false );
	}

	/**
	 * Register settings API.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register setting.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			'seofyme_seo',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @param mixed $input Input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = self::all();

		$out['title_separator']      = isset( $input['title_separator'] ) ? sanitize_text_field( $input['title_separator'] ) : $defaults['title_separator'];
		$out['homepage_title']       = isset( $input['homepage_title'] ) ? sanitize_text_field( $input['homepage_title'] ) : '';
		$out['homepage_description'] = isset( $input['homepage_description'] ) ? sanitize_textarea_field( $input['homepage_description'] ) : '';
		$out['organization_name']    = isset( $input['organization_name'] ) ? sanitize_text_field( $input['organization_name'] ) : '';
		$out['organization_logo']    = isset( $input['organization_logo'] ) ? esc_url_raw( $input['organization_logo'] ) : '';
		$out['ai_provider']          = isset( $input['ai_provider'] ) ? sanitize_key( $input['ai_provider'] ) : 'openai';
		$out['ai_api_key']           = isset( $input['ai_api_key'] ) ? sanitize_text_field( $input['ai_api_key'] ) : '';
		$out['indexnow_key']         = isset( $input['indexnow_key'] ) ? sanitize_text_field( $input['indexnow_key'] ) : '';
		$out['bot_blocker']          = isset( $input['bot_blocker'] ) && is_array( $input['bot_blocker'] ) ? array_map( 'sanitize_text_field', $input['bot_blocker'] ) : array();

		foreach ( array( 'noindex_search', 'noindex_author', 'xml_sitemap', 'schema_enabled', 'breadcrumbs', 'schema_aggregate' ) as $bool_key ) {
			$out[ $bool_key ] = ! empty( $input[ $bool_key ] );
		}

		return $out;
	}
}
