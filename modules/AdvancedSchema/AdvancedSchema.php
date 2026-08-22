<?php
/**
 * Advanced per-post schema types.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\AdvancedSchema;

use SeofymeSEO\Schema\Json_Ld;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ / HowTo / Product / Recipe / Course schema picker.
 */
class AdvancedSchema {

	public const META = '_seofyme_schema_type';
	public const DATA = '_seofyme_schema_data';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'wp_head', array( $this, 'output' ), 8 );
	}

	/**
	 * Types.
	 *
	 * @return array
	 */
	public static function types() {
		return array(
			''       => __( 'Default (Article/WebPage)', 'seofyme-seo' ),
			'FAQPage'=> __( 'FAQ', 'seofyme-seo' ),
			'HowTo'  => __( 'HowTo', 'seofyme-seo' ),
			'Product'=> __( 'Product', 'seofyme-seo' ),
			'Recipe' => __( 'Recipe', 'seofyme-seo' ),
			'Course' => __( 'Course', 'seofyme-seo' ),
			'Event'  => __( 'Event', 'seofyme-seo' ),
		);
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
			add_meta_box( 'seofyme_schema', __( 'Advanced schema', 'seofyme-seo' ), array( $this, 'render' ), $type, 'side' );
		}
	}

	/**
	 * Render.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'seofyme_schema', 'seofyme_schema_nonce' );
		$type = get_post_meta( $post->ID, self::META, true );
		$data = get_post_meta( $post->ID, self::DATA, true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		echo '<p><label>' . esc_html__( 'Schema type', 'seofyme-seo' ) . '<br><select class="widefat" name="seofyme_schema_type">';
		foreach ( self::types() as $key => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $type, $key, false ), esc_html( $label ) );
		}
		echo '</select></label></p>';
		printf(
			'<p><label>%s<br><textarea class="widefat" rows="5" name="seofyme_schema_data" placeholder="%s">%s</textarea></label></p>',
			esc_html__( 'Extra JSON fields (optional)', 'seofyme-seo' ),
			esc_attr__( '{"questions":[{"q":"...","a":"..."}]}', 'seofyme-seo' ),
			esc_textarea( wp_json_encode( $data ) === '[]' || wp_json_encode( $data ) === '{}' ? '' : Json_Ld::encode( $data, JSON_PRETTY_PRINT ) )
		);
		echo '<p class="description">' . esc_html__( 'For FAQ: {"questions":[{"q":"...","a":"..."}]}. For HowTo: {"steps":["...","..."]}.', 'seofyme-seo' ) . '</p>';
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_schema_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_schema_nonce'] ) ), 'seofyme_schema' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$type = isset( $_POST['seofyme_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['seofyme_schema_type'] ) ) : '';
		if ( ! array_key_exists( $type, self::types() ) ) {
			$type = '';
		}
		update_post_meta( $post_id, self::META, $type );

		$raw = '';
		if ( isset( $_POST['seofyme_schema_data'] ) ) {
			$raw = sanitize_textarea_field( wp_unslash( $_POST['seofyme_schema_data'] ) );
		}
		$decoded = json_decode( $raw, true );
		update_post_meta( $post_id, self::DATA, is_array( $decoded ) ? $this->sanitize_schema_data( $decoded ) : array() );
	}

	/**
	 * Output JSON-LD.
	 *
	 * @return void
	 */
	public function output() {
		if ( ! is_singular() ) {
			return;
		}
		$id   = get_queried_object_id();
		$type = get_post_meta( $id, self::META, true );
		if ( ! $type ) {
			return;
		}
		$data = get_post_meta( $id, self::DATA, true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$graph = $this->build( $type, $id, $data );
		if ( $graph ) {
			Json_Ld::print_script( $graph );
		}
	}

	/**
	 * Recursively sanitize decoded schema JSON.
	 *
	 * json_decode() does not sanitize values. Walk the tree and apply the
	 * most restrictive WordPress sanitizer for each leaf.
	 *
	 * @param mixed $value Value.
	 * @param int   $depth Depth.
	 * @return mixed
	 */
	private function sanitize_schema_data( $value, $depth = 0 ) {
		if ( $depth > 8 ) {
			return '';
		}

		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				if ( is_int( $key ) ) {
					$clean_key = $key;
				} else {
					$clean_key = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $key );
					if ( '' === $clean_key ) {
						continue;
					}
				}
				$clean[ $clean_key ] = $this->sanitize_schema_data( $item, $depth + 1 );
			}
			return $clean;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		$trimmed = trim( $value );
		if ( '' === $trimmed ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $trimmed ) ) {
			return esc_url_raw( $trimmed );
		}

		if ( is_email( $trimmed ) ) {
			return sanitize_email( $trimmed );
		}

		return sanitize_textarea_field( $trimmed );
	}

	/**
	 * Build schema object.
	 *
	 * @param string $type Type.
	 * @param int    $id Post ID.
	 * @param array  $data Extra.
	 * @return array|null
	 */
	private function build( $type, $id, array $data ) {
		$title = get_the_title( $id );
		$url   = get_permalink( $id );
		switch ( $type ) {
			case 'FAQPage':
				$entities = array();
				foreach ( (array) ( $data['questions'] ?? array() ) as $qa ) {
					$entities[] = array(
						'@type'          => 'Question',
						'name'           => $qa['q'] ?? '',
						'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa['a'] ?? '' ),
					);
				}
				return array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities );
			case 'HowTo':
				$steps = array();
				foreach ( array_values( (array) ( $data['steps'] ?? array() ) ) as $i => $step ) {
					$steps[] = array( '@type' => 'HowToStep', 'position' => $i + 1, 'text' => is_array( $step ) ? ( $step['text'] ?? '' ) : $step );
				}
				return array( '@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => $title, 'step' => $steps );
			case 'Product':
				return array(
					'@context' => 'https://schema.org',
					'@type'    => 'Product',
					'name'     => $data['name'] ?? $title,
					'url'      => $url,
					'description' => $data['description'] ?? wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 40 ),
					'offers'   => array(
						'@type'         => 'Offer',
						'price'         => $data['price'] ?? '',
						'priceCurrency' => $data['currency'] ?? 'EUR',
						'availability'  => 'https://schema.org/InStock',
					),
				);
			case 'Recipe':
				return array(
					'@context' => 'https://schema.org',
					'@type'    => 'Recipe',
					'name'     => $title,
					'recipeIngredient' => $data['ingredients'] ?? array(),
					'recipeInstructions' => $data['instructions'] ?? array(),
				);
			case 'Course':
				return array( '@context' => 'https://schema.org', '@type' => 'Course', 'name' => $title, 'description' => $data['description'] ?? '', 'provider' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ) );
			case 'Event':
				return array(
					'@context'  => 'https://schema.org',
					'@type'     => 'Event',
					'name'      => $title,
					'startDate' => $data['startDate'] ?? get_the_date( 'c', $id ),
					'location'  => $data['location'] ?? get_bloginfo( 'name' ),
					'url'       => $url,
				);
		}
		return null;
	}
}
