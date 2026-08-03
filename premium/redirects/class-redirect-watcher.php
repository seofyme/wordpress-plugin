<?php
/**
 * Prompt to create redirects when slugs change or posts are deleted.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Watches content moves/deletes and offers redirect creation.
 */
class Seofyme_Redirect_Watcher {

	/**
	 * Repository.
	 *
	 * @var Seofyme_Redirect_Repository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new Seofyme_Redirect_Repository();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'post_updated', [ $this, 'on_post_updated' ], 10, 3 );
		add_action( 'wp_trash_post', [ $this, 'on_trash' ] );
		add_action( 'before_delete_post', [ $this, 'on_delete' ] );
		add_action( 'admin_notices', [ $this, 'render_notice' ] );
		add_action( 'wp_ajax_seofyme_create_suggested_redirect', [ $this, 'ajax_create' ] );
	}

	/**
	 * Detect slug change.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post_after After.
	 * @param WP_Post $post_before Before.
	 * @return void
	 */
	public function on_post_updated( $post_id, $post_after, $post_before ) {
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post_after->post_name === $post_before->post_name || $post_before->post_status !== 'publish' ) {
			return;
		}
		if ( $post_after->post_status !== 'publish' ) {
			return;
		}

		$old = wp_parse_url( get_permalink( $post_before ), PHP_URL_PATH );
		$new = get_permalink( $post_after );
		if ( ! $old || ! $new ) {
			return;
		}

		$this->queue_suggestion( $old, $new, (int) $post_id );
	}

	/**
	 * On trash.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_trash( $post_id ) {
		$this->suggest_home_redirect( $post_id );
	}

	/**
	 * On delete.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_delete( $post_id ) {
		$this->suggest_home_redirect( $post_id );
	}

	/**
	 * Suggest redirect to home for removed content.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function suggest_home_redirect( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return;
		}
		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
		if ( $path ) {
			$this->queue_suggestion( $path, home_url( '/' ), (int) $post_id );
		}
	}

	/**
	 * Store suggestion in transient data for current user.
	 *
	 * @param string $origin Origin.
	 * @param string $target Target.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	private function queue_suggestion( $origin, $target, $post_id ) {
		$key  = 'seofyme_redirect_suggest_' . get_current_user_id();
		$list = get_transient( $key );
		if ( ! is_array( $list ) ) {
			$list = [];
		}
		$list[] = [
			'origin'  => $origin,
			'target'  => $target,
			'post_id' => $post_id,
		];
		set_transient( $key, $list, HOUR_IN_SECONDS );
	}

	/**
	 * Admin notice with one-click create.
	 *
	 * @return void
	 */
	public function render_notice() {
		$key  = 'seofyme_redirect_suggest_' . get_current_user_id();
		$list = get_transient( $key );
		if ( empty( $list ) || ! is_array( $list ) ) {
			return;
		}

		foreach ( $list as $i => $item ) {
			$origin = esc_html( $item['origin'] );
			$target = esc_html( $item['target'] );
			?>
			<div class="notice notice-warning is-dismissible seofyme-redirect-suggest">
				<p>
					<?php
					printf(
						/* translators: 1: old path, 2: new URL */
						esc_html__( 'Seofyme detected a URL change from %1$s to %2$s. Create a 301 redirect?', 'seofyme-seo' ),
						'<code>' . $origin . '</code>',
						'<code>' . $target . '</code>'
					);
					?>
					<button type="button" class="button button-primary seofyme-create-redirect"
						data-origin="<?php echo esc_attr( $item['origin'] ); ?>"
						data-target="<?php echo esc_attr( $item['target'] ); ?>"
						data-index="<?php echo esc_attr( (string) $i ); ?>">
						<?php esc_html_e( 'Create redirect', 'seofyme-seo' ); ?>
					</button>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX create from suggestion.
	 *
	 * @return void
	 */
	public function ajax_create() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		$origin = isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$index  = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;

		$id = $this->repo->create( $origin, $target, 301 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'failed' ] );
		}

		$key  = 'seofyme_redirect_suggest_' . get_current_user_id();
		$list = get_transient( $key );
		if ( is_array( $list ) && isset( $list[ $index ] ) ) {
			unset( $list[ $index ] );
			set_transient( $key, array_values( $list ), HOUR_IN_SECONDS );
		}

		wp_send_json_success( [ 'id' => $id ] );
	}
}
