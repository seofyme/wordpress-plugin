<?php
/**
 * Front-end SEO inspector for editors.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\FrontendInspector;

use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Floating edit panel on the front end.
 */
class FrontendInspector {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ), 100 );
		add_action( 'wp_ajax_seofyme_fe_save', array( $this, 'ajax_save' ) );
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
		wp_enqueue_style(
			'seofyme-seo-fonts',
			'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Sora:wght@400;500;600;700&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'seofyme-fe', SEOFYME_SEO_URL . 'assets/css/frontend-inspector.css', array( 'seofyme-seo-fonts' ), SEOFYME_SEO_VERSION );
		wp_enqueue_script( 'seofyme-fe', SEOFYME_SEO_URL . 'assets/js/frontend-inspector.js', array(), SEOFYME_SEO_VERSION, true );
		wp_localize_script(
			'seofyme-fe',
			'seofymeFE',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'seofyme_seo' ),
				'postId'  => get_queried_object_id(),
			)
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! is_singular() || ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
			return;
		}
		$id = get_queried_object_id();
		?>
		<div id="seofyme-fe-inspector" hidden>
			<button type="button" id="seofyme-fe-toggle"><?php esc_html_e( 'SEO', 'seofyme-seo' ); ?></button>
			<div id="seofyme-fe-panel">
				<h3><?php esc_html_e( 'Seofyme inspector', 'seofyme-seo' ); ?></h3>
				<label><?php esc_html_e( 'SEO title', 'seofyme-seo' ); ?><input id="seofyme-fe-title" type="text" value="<?php echo esc_attr( Post_Meta::get( $id, Post_Meta::TITLE ) ); ?>" /></label>
				<label><?php esc_html_e( 'Meta description', 'seofyme-seo' ); ?><textarea id="seofyme-fe-desc" rows="3"><?php echo esc_textarea( Post_Meta::get( $id, Post_Meta::DESCRIPTION ) ); ?></textarea></label>
				<button type="button" id="seofyme-fe-save"><?php esc_html_e( 'Save', 'seofyme-seo' ); ?></button>
				<p class="seofyme-fe-status"></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save.
	 *
	 * @return void
	 */
	public function ajax_save() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		Post_Meta::set( $post_id, Post_Meta::TITLE, isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '' );
		Post_Meta::set( $post_id, Post_Meta::DESCRIPTION, isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '' );
		wp_send_json_success( array( 'saved' => true ) );
	}
}
