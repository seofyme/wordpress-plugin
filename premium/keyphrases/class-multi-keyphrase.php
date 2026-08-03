<?php
/**
 * Up to 5 focus keyphrases per post (with synonyms).
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multi-keyphrase meta box and analysis helpers.
 */
class Seofyme_Multi_Keyphrase {

	public const META_KEY = '_seofyme_keyphrases';
	public const MAX      = 5;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save' ], 20, 2 );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_filter( 'wpseo_frontend_presenter_classes', [ $this, 'maybe_noop' ] );
	}

	/**
	 * Meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$types = get_post_types( [ 'public' => true ], 'names' );
		foreach ( $types as $type ) {
			add_meta_box(
				'seofyme_multi_keyphrase',
				__( 'Seofyme — Related keyphrases', 'seofyme-seo' ),
				[ $this, 'render_meta_box' ],
				$type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'seofyme_keyphrases', 'seofyme_keyphrases_nonce' );
		$items = $this->get_keyphrases( $post->ID );
		while ( count( $items ) < self::MAX ) {
			$items[] = [ 'keyphrase' => '', 'synonyms' => '' ];
		}
		echo '<p class="description">' . esc_html__( 'Optimize for up to 5 keyphrases. Synonyms and word forms count toward analysis.', 'seofyme-seo' ) . '</p>';
		foreach ( $items as $i => $item ) {
			printf(
				'<p><label>%1$s %2$d<br><input type="text" class="widefat" name="seofyme_kp[%2$d][keyphrase]" value="%3$s" /></label></p>',
				esc_html__( 'Keyphrase', 'seofyme-seo' ),
				(int) ( $i + 1 ),
				esc_attr( $item['keyphrase'] )
			);
			printf(
				'<p><label>%1$s<br><input type="text" class="widefat" name="seofyme_kp[%2$d][synonyms]" value="%3$s" placeholder="%4$s" /></label></p>',
				esc_html__( 'Synonyms (comma-separated)', 'seofyme-seo' ),
				(int) $i,
				esc_attr( $item['synonyms'] ),
				esc_attr__( 'e.g. shoes, sneakers', 'seofyme-seo' )
			);
		}
	}

	/**
	 * Save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['seofyme_keyphrases_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_keyphrases_nonce'] ) ), 'seofyme_keyphrases' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw   = isset( $_POST['seofyme_kp'] ) && is_array( $_POST['seofyme_kp'] ) ? wp_unslash( $_POST['seofyme_kp'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$clean = [];
		foreach ( array_slice( $raw, 0, self::MAX ) as $row ) {
			$kp = isset( $row['keyphrase'] ) ? sanitize_text_field( $row['keyphrase'] ) : '';
			$sy = isset( $row['synonyms'] ) ? sanitize_text_field( $row['synonyms'] ) : '';
			if ( $kp !== '' ) {
				$clean[] = [
					'keyphrase' => $kp,
					'synonyms'  => $sy,
				];
			}
		}
		update_post_meta( $post_id, self::META_KEY, $clean );

		// Keep first related keyphrase mirrored for analysis consumers.
		if ( ! empty( $clean[0]['keyphrase'] ) && ! get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_focuskw', $clean[0]['keyphrase'] );
		}
	}

	/**
	 * Get keyphrases.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_keyphrases( $post_id ) {
		$items = get_post_meta( $post_id, self::META_KEY, true );
		return is_array( $items ) ? $items : [];
	}

	/**
	 * Score content against a keyphrase set.
	 *
	 * @param string $content Content.
	 * @param string $title Title.
	 * @param array  $item Keyphrase item.
	 * @return array
	 */
	public function analyze( $content, $title, array $item ) {
		$text   = strtolower( wp_strip_all_tags( $content ) );
		$title  = strtolower( $title );
		$terms  = array_filter( array_merge( [ $item['keyphrase'] ], array_map( 'trim', explode( ',', $item['synonyms'] ?? '' ) ) ) );
		$hits   = 0;
		$in_title = false;
		foreach ( $terms as $term ) {
			$term = strtolower( $term );
			if ( $term === '' ) {
				continue;
			}
			$hits += substr_count( $text, $term );
			if ( strpos( $title, $term ) !== false ) {
				$in_title = true;
			}
		}
		$words = max( 1, str_word_count( $text ) );
		$density = round( ( $hits / $words ) * 100, 2 );

		return [
			'keyphrase'   => $item['keyphrase'],
			'occurrences' => $hits,
			'density'     => $density,
			'in_title'    => $in_title,
			'score'       => min( 100, ( $hits > 0 ? 40 : 0 ) + ( $in_title ? 30 : 0 ) + ( $density >= 0.5 && $density <= 2.5 ? 30 : 10 ) ),
		];
	}

	/**
	 * REST routes for editor integrations.
	 *
	 * @return void
	 */
	public function register_rest() {
		register_rest_route(
			'seofyme/v1',
			'/keyphrases/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'permission_callback' => static function ( $req ) {
					return current_user_can( 'edit_post', (int) $req['id'] );
				},
				'callback'            => function ( $req ) {
					$post = get_post( (int) $req['id'] );
					if ( ! $post ) {
						return new WP_Error( 'not_found', 'Post not found', [ 'status' => 404 ] );
					}
					$results = [];
					foreach ( $this->get_keyphrases( $post->ID ) as $item ) {
						$results[] = $this->analyze( $post->post_content, $post->post_title, $item );
					}
					return rest_ensure_response( $results );
				},
			]
		);
	}

	/**
	 * Placeholder filter compatibility.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function maybe_noop( $classes ) {
		return $classes;
	}
}
