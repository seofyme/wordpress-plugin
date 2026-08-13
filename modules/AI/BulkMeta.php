<?php
/**
 * Bulk metadata gaps + approve flow.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\AI;

use SeofymeSEO\Admin\Page_Shell;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk editor for missing titles/descriptions.
 */
class BulkMeta {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_seofyme_bulk_draft', array( $this, 'ajax_draft' ) );
		add_action( 'wp_ajax_seofyme_bulk_apply', array( $this, 'ajax_apply' ) );
	}

	/**
	 * Missing meta rows.
	 *
	 * @return array
	 */
	public function missing() {
		$q   = new \WP_Query(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'modified',
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $q->posts as $post ) {
			$title = Post_Meta::get( $post->ID, Post_Meta::TITLE );
			$desc  = Post_Meta::get( $post->ID, Post_Meta::DESCRIPTION );
			if ( $title && $desc ) {
				continue;
			}
			$out[] = array(
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'seo_title' => $title,
				'seo_desc'  => $desc,
			);
		}
		return $out;
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$items = $this->missing();
		Page_Shell::open(
			__( 'Bulk editor', 'seofyme-seo' ),
			__( 'Draft missing titles and descriptions, review each one, then approve to save.', 'seofyme-seo' )
		);
		?>
			<section class="sf-card">
				<header class="sf-card__header">
					<h2><?php esc_html_e( 'Missing meta', 'seofyme-seo' ); ?></h2>
					<p><?php esc_html_e( 'Posts and pages without an SEO title or description.', 'seofyme-seo' ); ?></p>
				</header>
				<div class="sf-card__body sf-card__body--table">
					<table class="sf-table sf-table--data" id="seofyme-bulk-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Content', 'seofyme-seo' ); ?></th>
								<th><?php esc_html_e( 'SEO title', 'seofyme-seo' ); ?></th>
								<th><?php esc_html_e( 'Description', 'seofyme-seo' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'seofyme-seo' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php if ( empty( $items ) ) : ?>
							<tr><td class="sf-table__empty" colspan="4"><?php esc_html_e( 'No gaps found.', 'seofyme-seo' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $items as $item ) : ?>
								<tr data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
									<td><strong><?php echo esc_html( $item['title'] ); ?></strong></td>
									<td><input type="text" class="sf-input sf-input--full seofyme-bulk-title" value="<?php echo esc_attr( $item['seo_title'] ); ?>" /></td>
									<td><textarea class="sf-textarea sf-input--full seofyme-bulk-desc" rows="3"><?php echo esc_textarea( $item['seo_desc'] ); ?></textarea></td>
									<td>
										<div class="sf-table__actions">
											<button type="button" class="sf-btn sf-btn--secondary sf-btn--small seofyme-bulk-draft"><?php esc_html_e( 'AI draft', 'seofyme-seo' ); ?></button>
											<button type="button" class="sf-btn sf-btn--primary sf-btn--small seofyme-bulk-apply"><?php esc_html_e( 'Approve', 'seofyme-seo' ); ?></button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php
		Page_Shell::close();
	}

	/**
	 * Draft AJAX.
	 *
	 * @return void
	 */
	public function ajax_draft() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		$ai     = new Generator();
		$titles = $ai->generate( $post_id, 'titles' );
		$metas  = $ai->generate( $post_id, 'metas' );
		wp_send_json_success(
			array(
				'title' => is_array( $titles ) ? ( $titles[0] ?? '' ) : '',
				'desc'  => is_array( $metas ) ? ( $metas[0] ?? '' ) : '',
			)
		);
	}

	/**
	 * Apply AJAX.
	 *
	 * @return void
	 */
	public function ajax_apply() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$desc  = isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '';
		if ( $title ) {
			Post_Meta::set( $post_id, Post_Meta::TITLE, $title );
		}
		if ( $desc ) {
			Post_Meta::set( $post_id, Post_Meta::DESCRIPTION, $desc );
		}
		wp_send_json_success( array( 'saved' => true ) );
	}
}
