<?php
/**
 * Facebook / X social appearance controls + preview meta.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social meta fields with admin preview.
 */
class Seofyme_Social_Previews {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save' ] );
		add_action( 'wp_head', [ $this, 'output_tags' ], 2 );
	}

	/**
	 * Meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $type ) {
			add_meta_box(
				'seofyme_social',
				__( 'Seofyme — Social preview', 'seofyme-seo' ),
				[ $this, 'render_meta_box' ],
				$type,
				'normal',
				'default'
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
		wp_nonce_field( 'seofyme_social', 'seofyme_social_nonce' );
		$fb_title = get_post_meta( $post->ID, '_seofyme_og_title', true );
		$fb_desc  = get_post_meta( $post->ID, '_seofyme_og_description', true );
		$fb_image = get_post_meta( $post->ID, '_seofyme_og_image', true );
		$x_title  = get_post_meta( $post->ID, '_seofyme_twitter_title', true );
		$x_desc   = get_post_meta( $post->ID, '_seofyme_twitter_description', true );
		$x_image  = get_post_meta( $post->ID, '_seofyme_twitter_image', true );

		$preview_title = $fb_title ?: $post->post_title;
		$preview_desc  = $fb_desc ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
		$preview_img   = $fb_image ?: get_the_post_thumbnail_url( $post, 'large' );
		?>
		<div class="seofyme-social-grid">
			<div>
				<h3><?php esc_html_e( 'Facebook / Open Graph', 'seofyme-seo' ); ?></h3>
				<p><label><?php esc_html_e( 'Title', 'seofyme-seo' ); ?><br><input type="text" class="widefat" name="seofyme_og_title" value="<?php echo esc_attr( (string) $fb_title ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Description', 'seofyme-seo' ); ?><br><textarea class="widefat" rows="3" name="seofyme_og_description"><?php echo esc_textarea( (string) $fb_desc ); ?></textarea></label></p>
				<p><label><?php esc_html_e( 'Image URL', 'seofyme-seo' ); ?><br><input type="url" class="widefat" name="seofyme_og_image" value="<?php echo esc_attr( (string) $fb_image ); ?>" /></label></p>
			</div>
			<div>
				<h3><?php esc_html_e( 'X (Twitter)', 'seofyme-seo' ); ?></h3>
				<p><label><?php esc_html_e( 'Title', 'seofyme-seo' ); ?><br><input type="text" class="widefat" name="seofyme_twitter_title" value="<?php echo esc_attr( (string) $x_title ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Description', 'seofyme-seo' ); ?><br><textarea class="widefat" rows="3" name="seofyme_twitter_description"><?php echo esc_textarea( (string) $x_desc ); ?></textarea></label></p>
				<p><label><?php esc_html_e( 'Image URL', 'seofyme-seo' ); ?><br><input type="url" class="widefat" name="seofyme_twitter_image" value="<?php echo esc_attr( (string) $x_image ); ?>" /></label></p>
			</div>
		</div>
		<div class="seofyme-social-preview-card">
			<strong><?php esc_html_e( 'Preview', 'seofyme-seo' ); ?></strong>
			<?php if ( $preview_img ) : ?>
				<img src="<?php echo esc_url( $preview_img ); ?>" alt="" style="max-width:100%;height:auto;display:block;margin:8px 0;" />
			<?php endif; ?>
			<div class="seofyme-preview-title"><?php echo esc_html( $preview_title ); ?></div>
			<div class="seofyme-preview-desc"><?php echo esc_html( $preview_desc ); ?></div>
			<div class="seofyme-preview-url"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
		</div>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( ! isset( $_POST['seofyme_social_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seofyme_social_nonce'] ) ), 'seofyme_social' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$map = [
			'seofyme_og_title'             => '_seofyme_og_title',
			'seofyme_og_description'       => '_seofyme_og_description',
			'seofyme_og_image'             => '_seofyme_og_image',
			'seofyme_twitter_title'        => '_seofyme_twitter_title',
			'seofyme_twitter_description'  => '_seofyme_twitter_description',
			'seofyme_twitter_image'        => '_seofyme_twitter_image',
		];

		foreach ( $map as $field => $meta ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			if ( strpos( $field, 'description' ) !== false ) {
				$value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
			}
			if ( strpos( $field, 'image' ) !== false ) {
				$value = esc_url_raw( wp_unslash( $_POST[ $field ] ) );
			}
			update_post_meta( $post_id, $meta, $value );

			// Sync into Yoast social meta keys when empty there, for presenter compatibility.
			$yoast_map = [
				'_seofyme_og_title'            => '_yoast_wpseo_opengraph-title',
				'_seofyme_og_description'      => '_yoast_wpseo_opengraph-description',
				'_seofyme_og_image'            => '_yoast_wpseo_opengraph-image',
				'_seofyme_twitter_title'       => '_yoast_wpseo_twitter-title',
				'_seofyme_twitter_description' => '_yoast_wpseo_twitter-description',
				'_seofyme_twitter_image'       => '_yoast_wpseo_twitter-image',
			];
			if ( isset( $yoast_map[ $meta ] ) && $value ) {
				update_post_meta( $post_id, $yoast_map[ $meta ], $value );
			}
		}
	}

	/**
	 * Output tags if core presenters miss them.
	 *
	 * @return void
	 */
	public function output_tags() {
		if ( ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		$title   = get_post_meta( $post_id, '_seofyme_og_title', true );
		$desc    = get_post_meta( $post_id, '_seofyme_og_description', true );
		$image   = get_post_meta( $post_id, '_seofyme_og_image', true );
		if ( $title ) {
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		}
		if ( $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		}
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
		}

		$xt = get_post_meta( $post_id, '_seofyme_twitter_title', true );
		$xd = get_post_meta( $post_id, '_seofyme_twitter_description', true );
		$xi = get_post_meta( $post_id, '_seofyme_twitter_image', true );
		if ( $xt ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $xt ) . '" />' . "\n";
		}
		if ( $xd ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $xd ) . '" />' . "\n";
		}
		if ( $xi ) {
			echo '<meta name="twitter:image" content="' . esc_url( $xi ) . '" />' . "\n";
		}
	}
}
