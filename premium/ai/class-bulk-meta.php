<?php
/**
 * Bulk metadata drafting and approval.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists posts missing titles/descriptions and applies approved drafts.
 */
class Seofyme_Bulk_Meta {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'menu' ], 100 );
		add_action( 'wp_ajax_seofyme_bulk_draft', [ $this, 'ajax_draft' ] );
		add_action( 'wp_ajax_seofyme_bulk_apply', [ $this, 'ajax_apply' ] );
	}

	/**
	 * Submenu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'wpseo_dashboard',
			__( 'Bulk editor', 'seofyme-seo' ),
			__( 'Bulk editor', 'seofyme-seo' ),
			'edit_others_posts',
			'seofyme-bulk',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Find posts missing SEO meta.
	 *
	 * @param string $missing title|description|either.
	 * @param int    $limit Limit.
	 * @return array
	 */
	public function find_missing( $missing = 'either', $limit = 50 ) {
		$q = new WP_Query(
			[
				'post_type'      => [ 'post', 'page' ],
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			]
		);

		$out = [];
		foreach ( $q->posts as $post ) {
			$title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
			$desc  = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
			$needs_title = ( $title === '' || $title === false );
			$needs_desc  = ( $desc === '' || $desc === false );
			if ( $missing === 'title' && ! $needs_title ) {
				continue;
			}
			if ( $missing === 'description' && ! $needs_desc ) {
				continue;
			}
			if ( $missing === 'either' && ! $needs_title && ! $needs_desc ) {
				continue;
			}
			$out[] = [
				'id'          => $post->ID,
				'title'       => get_the_title( $post ),
				'url'         => get_permalink( $post ),
				'seo_title'   => $title,
				'seo_desc'    => $desc,
				'needs_title' => $needs_title,
				'needs_desc'  => $needs_desc,
			];
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
		$items = $this->find_missing();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bulk metadata editor', 'seofyme-seo' ); ?></h1>
			<p><?php esc_html_e( 'Filter pages missing titles or descriptions, draft with AI, then approve before anything saves.', 'seofyme-seo' ); ?></p>
			<table class="widefat striped" id="seofyme-bulk-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Content', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'SEO title', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Meta description', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'seofyme-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $items ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No gaps found in the latest posts/pages.', 'seofyme-seo' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $items as $item ) : ?>
							<tr data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
								<td>
									<strong><?php echo esc_html( $item['title'] ); ?></strong><br>
									<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'seofyme-seo' ); ?></a>
								</td>
								<td><input type="text" class="widefat seofyme-bulk-title" value="<?php echo esc_attr( (string) $item['seo_title'] ); ?>" placeholder="<?php esc_attr_e( 'Missing', 'seofyme-seo' ); ?>" /></td>
								<td><textarea class="widefat seofyme-bulk-desc" rows="2" placeholder="<?php esc_attr_e( 'Missing', 'seofyme-seo' ); ?>"><?php echo esc_textarea( (string) $item['seo_desc'] ); ?></textarea></td>
								<td>
									<button type="button" class="button seofyme-bulk-draft"><?php esc_html_e( 'AI draft', 'seofyme-seo' ); ?></button>
									<button type="button" class="button button-primary seofyme-bulk-apply"><?php esc_html_e( 'Approve & save', 'seofyme-seo' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * AJAX draft one row.
	 *
	 * @return void
	 */
	public function ajax_draft() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		$ai     = new Seofyme_AI_Generator();
		$titles = $ai->generate( $post_id, 'titles' );
		$metas  = $ai->generate( $post_id, 'metas' );
		wp_send_json_success(
			[
				'title' => is_array( $titles ) ? ( $titles[0] ?? '' ) : '',
				'desc'  => is_array( $metas ) ? ( $metas[0] ?? '' ) : '',
			]
		);
	}

	/**
	 * AJAX apply approved values.
	 *
	 * @return void
	 */
	public function ajax_apply() {
		check_ajax_referer( 'seofyme_premium', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$desc    = isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '';
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		if ( $title !== '' ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', $title );
		}
		if ( $desc !== '' ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $desc );
		}
		wp_send_json_success( [ 'saved' => true ] );
	}
}
