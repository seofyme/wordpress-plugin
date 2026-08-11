<?php
/**
 * Link Assistant — stronger internal linking automation.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\LinkAssistant;

use SeofymeSEO\Admin\Page_Shell;
use SeofymeSEO\Modules\InternalLinking\InternalLinking;
use SeofymeSEO\Modules\InternalLinking\OrphanedContent;
use SeofymeSEO\Support\Post_Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide linking opportunities + one-click insert.
 */
class LinkAssistant {

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_seofyme_insert_link', array( $this, 'ajax_insert' ) );
	}

	/**
	 * Opportunities: orphaned posts + suggested sources.
	 *
	 * @return array
	 */
	public function opportunities() {
		$orphans = ( new OrphanedContent() )->find( 20 );
		$linker  = new InternalLinking();
		$out     = array();
		foreach ( $orphans as $orphan ) {
			// Find a recent related post that could link to the orphan.
			$candidates = get_posts(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => 6,
					's'              => $orphan['title'],
					'no_found_rows'  => true,
				)
			);
			$source = null;
			foreach ( $candidates as $candidate ) {
				if ( (int) $candidate->ID !== (int) $orphan['id'] ) {
					$source = $candidate;
					break;
				}
			}
			$out[]  = array(
				'orphan'      => $orphan,
				'source_id'   => $source ? $source->ID : 0,
				'source_title'=> $source ? get_the_title( $source ) : '',
				'suggestions' => $linker->suggest( $orphan['id'], 3 ),
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
		$rows = $this->opportunities();
		Page_Shell::open(
			__( 'Link assistant', 'seofyme-seo' ),
			__( 'Find orphaned pages and insert contextual internal links from related content.', 'seofyme-seo' )
		);
		?>
		<section class="seofyme-panel">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Orphaned page', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Suggested source', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Action', 'seofyme-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No linking opportunities found.', 'seofyme-seo' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $row['orphan']['id'] ) ); ?>"><?php echo esc_html( $row['orphan']['title'] ); ?></a></td>
							<td><?php echo esc_html( $row['source_title'] ?: '—' ); ?></td>
							<td>
								<?php if ( $row['source_id'] ) : ?>
									<button type="button" class="button button-primary seofyme-insert-link"
										data-source="<?php echo esc_attr( (string) $row['source_id'] ); ?>"
										data-target="<?php echo esc_attr( (string) $row['orphan']['id'] ); ?>"
										data-title="<?php echo esc_attr( $row['orphan']['title'] ); ?>"
										data-url="<?php echo esc_attr( $row['orphan']['url'] ); ?>">
										<?php esc_html_e( 'Insert link', 'seofyme-seo' ); ?>
									</button>
								<?php endif; ?>
							</td>
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
	 * Insert a paragraph with link into source content.
	 *
	 * @return void
	 */
	public function ajax_insert() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		$source = isset( $_POST['source'] ) ? (int) $_POST['source'] : 0;
		$url    = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( ! $source || ! current_user_can( 'edit_post', $source ) || ! $url ) {
			wp_send_json_error( null, 403 );
		}
		$post = get_post( $source );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => 'missing' ) );
		}
		if ( false !== strpos( $post->post_content, $url ) ) {
			wp_send_json_success( array( 'already' => true ) );
		}
		$block  = "\n\n<!-- wp:paragraph --><p>" . sprintf(
			/* translators: %s link */
			esc_html__( 'Related reading: %s', 'seofyme-seo' ),
			'<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
		) . "</p><!-- /wp:paragraph -->\n";
		wp_update_post(
			array(
				'ID'           => $source,
				'post_content' => $post->post_content . $block,
			)
		);
		wp_send_json_success( array( 'inserted' => true ) );
	}
}
