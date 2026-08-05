<?php
/**
 * Gutenberg block for related internal links.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\InternalLinking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers seofyme/related-links block.
 */
class Linking_Block {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Block + editor script.
	 *
	 * @return void
	 */
	public function register_block() {
		wp_register_script(
			'seofyme-related-links-block',
			SEOFYME_SEO_URL . 'assets/js/related-links-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			SEOFYME_SEO_VERSION,
			true
		);

		register_block_type(
			'seofyme/related-links',
			array(
				'api_version'     => 2,
				'title'           => __( 'Related internal links', 'seofyme-seo' ),
				'description'     => __( 'Suggests related posts to strengthen site structure.', 'seofyme-seo' ),
				'category'        => 'widgets',
				'icon'            => 'admin-links',
				'editor_script'   => 'seofyme-related-links-block',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'count' => array(
						'type'    => 'number',
						'default' => 5,
					),
					'title' => array(
						'type'    => 'string',
						'default' => __( 'Related reading', 'seofyme-seo' ),
					),
				),
			)
		);
	}

	/**
	 * Front-end render.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	public function render( $attrs ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$count = isset( $attrs['count'] ) ? max( 1, min( 12, (int) $attrs['count'] ) ) : 5;
		$title = isset( $attrs['title'] ) ? (string) $attrs['title'] : __( 'Related reading', 'seofyme-seo' );
		$items = ( new InternalLinking() )->suggest( $post_id );
		$items = array_slice( $items, 0, $count );
		if ( empty( $items ) ) {
			return '';
		}

		$html  = '<nav class="seofyme-related-links" aria-label="' . esc_attr( $title ) . '">';
		$html .= '<h3 class="seofyme-related-links__title">' . esc_html( $title ) . '</h3><ul>';
		foreach ( $items as $item ) {
			$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
		}
		$html .= '</ul></nav>';
		return $html;
	}
}
