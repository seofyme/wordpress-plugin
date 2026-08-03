<?php
/**
 * Front-end SEO inspector for logged-in editors.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Floating inspector to view/edit title, description, schema summary.
 */
class Seofyme_Frontend_Inspector {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_footer', [ $this, 'render' ], 100 );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'wp_ajax_seofyme_fe_save', [ $this, 'ajax_save' ] );
	}

	/**
	 * Assets.
	 *
	 * @return void
	 */
	public function assets() {
		if ( ! is_singular() || ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
			return;
		}
		wp_enqueue_style( 'seofyme-fe-inspector', SEOFYME_PREMIUM_URL . 'assets/frontend-inspector.css', [], SEOFYME_SEO_VERSION );
		wp_enqueue_script( 'seofyme-fe-inspector', SEOFYME_PREMIUM_URL . 'assets/frontend-inspector.js', [], SEOFYME_SEO_VERSION, true );
		wp_localize_script(
			'seofyme-fe-inspector',
			'seofymeFE',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seofyme_premium' ),
				'postId'  => get_queried_object_id(),
			]
		);
	}

	/**
	 * Render panel.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! is_singular() || ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
			return;
		}
		$id    = get_queried_object_id();
		$title = get_post_meta( $id, '_yoast_wpseo_title', true );
		$desc  = get_post_meta( $id, '_yoast_wpseo_metadesc', true );
		?>
		<div id="seofyme-fe-inspector" hidden>
			<button type="button" id="seofyme-fe-toggle" aria-expanded="false"><?php esc_html_e( 'SEO', 'seofyme-seo' ); ?></button>
			<div id="seofyme-fe-panel">
				<h3><?php esc_html_e( 'Seofyme front-end inspector', 'seofyme-seo' ); ?></h3>
				<label><?php esc_html_e( 'SEO title', 'seofyme-seo' ); ?>
					<input type="text" id="seofyme-fe-title" value="<?php echo esc_attr( (string) $title ); ?>" />
				</label>
				<label><?php esc_html_e( 'Meta description', 'seofyme-seo' ); ?>
					<textarea id="seofyme-fe-desc" rows="3"><?php echo esc_textarea( (string) $desc ); ?></textarea>
				</label>
				<button type="button" class="button" id="seofyme-fe-save"><?php esc_html_e( 'Save', 'seofyme-seo' ); ?></button>
				<p class="seofyme-fe-status" aria-live="polite"></p>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX save.
	 *
	 * @return void
	 */
	public function ajax_save() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$desc  = isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '';
		update_post_meta( $post_id, '_yoast_wpseo_title', $title );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $desc );
		wp_send_json_success( [ 'saved' => true ] );
	}
}
