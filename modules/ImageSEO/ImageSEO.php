<?php
/**
 * Image SEO — alt/title helpers and bulk editor.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\ImageSEO;

use SeofymeSEO\Admin\Page_Shell;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Improves image SEO coverage.
 */
class ImageSEO {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'auto_alt' ), 10, 2 );
		add_action( 'wp_ajax_seofyme_image_save', array( $this, 'ajax_save' ) );
	}

	/**
	 * Auto-fill empty alt from filename/title.
	 *
	 * @param array $metadata Metadata.
	 * @param int   $attachment_id ID.
	 * @return array
	 */
	public function auto_alt( $metadata, $attachment_id ) {
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( $alt ) {
			return $metadata;
		}
		$title = get_the_title( $attachment_id );
		if ( ! $title ) {
			$file  = get_attached_file( $attachment_id );
			$title = $file ? pathinfo( $file, PATHINFO_FILENAME ) : '';
			$title = ucwords( str_replace( array( '-', '_' ), ' ', $title ) );
		}
		if ( $title ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
		}
		return $metadata;
	}

	/**
	 * Missing alt images.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function missing( $limit = 50 ) {
		$q = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image',
				'posts_per_page' => $limit,
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $q->posts as $img ) {
			$alt = get_post_meta( $img->ID, '_wp_attachment_image_alt', true );
			if ( $alt ) {
				continue;
			}
			$out[] = array(
				'id'    => $img->ID,
				'title' => get_the_title( $img ),
				'url'   => wp_get_attachment_url( $img->ID ),
				'alt'   => '',
			);
		}
		return $out;
	}

	/**
	 * Admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}
		$items = $this->missing();
		Page_Shell::open( __( 'Image SEO', 'seofyme-seo' ), __( 'Fill missing alt text in bulk. New uploads get a filename-based alt automatically.', 'seofyme-seo' ) );
		?>
		<section class="seofyme-panel">
			<table class="widefat striped" id="seofyme-image-table">
				<thead><tr><th><?php esc_html_e( 'Image', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Alt text', 'seofyme-seo' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No images missing alt text in this scan.', 'seofyme-seo' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $items as $item ) : ?>
						<tr data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
							<td>
								<?php if ( $item['url'] ) : ?>
									<img src="<?php echo esc_url( $item['url'] ); ?>" alt="" style="max-width:64px;height:auto;border-radius:8px;vertical-align:middle;margin-right:8px" />
								<?php endif; ?>
								<?php echo esc_html( $item['title'] ); ?>
							</td>
							<td><input class="widefat seofyme-image-alt" value="<?php echo esc_attr( $item['title'] ); ?>" /></td>
							<td><button type="button" class="button button-primary seofyme-image-save"><?php esc_html_e( 'Save', 'seofyme-seo' ); ?></button></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</section>
		<?php
		Page_Shell::close();
	}

	/**
	 * AJAX save alt.
	 *
	 * @return void
	 */
	public function ajax_save() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$id  = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$alt = isset( $_POST['alt'] ) ? sanitize_text_field( wp_unslash( $_POST['alt'] ) ) : '';
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_send_json_error( null, 403 );
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		wp_send_json_success( array( 'saved' => true ) );
	}
}
