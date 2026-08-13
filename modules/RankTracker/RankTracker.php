<?php
/**
 * Keyword rank tracker (manual + import-ready).
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\RankTracker;

use SeofymeSEO\Admin\Page_Shell;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks keywords and position snapshots.
 */
class RankTracker {

	public const OPTION = 'seofyme_rank_keywords';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_post_seofyme_rank_add', array( $this, 'handle_add' ) );
		add_action( 'admin_post_seofyme_rank_update', array( $this, 'handle_update' ) );
		add_action( 'admin_post_seofyme_rank_delete', array( $this, 'handle_delete' ) );
	}

	/**
	 * All keywords.
	 *
	 * @return array
	 */
	public function all() {
		$rows = get_option( self::OPTION, array() );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Save list.
	 *
	 * @param array $rows Rows.
	 * @return void
	 */
	private function save( array $rows ) {
		update_option( self::OPTION, array_values( $rows ), false );
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
		$rows = $this->all();
		Page_Shell::open(
			__( 'Rank tracker', 'seofyme-seo' ),
			__( 'Track focus keywords and log positions over time. Connect Search Console later for automation.', 'seofyme-seo' )
		);
		?>
		<section class="sf-card">
			<header class="sf-card__header">
				<h2><?php esc_html_e( 'Add keyword', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Start tracking a keyword and its target URL.', 'seofyme-seo' ); ?></p>
			</header>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="seofyme_rank_add" />
				<?php wp_nonce_field( 'seofyme_rank_add' ); ?>
				<div class="sf-card__body">
					<div class="sf-field sf-field--stack">
						<label class="sf-field__label" for="sf-rank-keyword"><?php esc_html_e( 'Keyword', 'seofyme-seo' ); ?></label>
						<input type="text" id="sf-rank-keyword" name="keyword" class="sf-input" required />
					</div>
					<div class="sf-field sf-field--stack">
						<label class="sf-field__label" for="sf-rank-url"><?php esc_html_e( 'Target URL', 'seofyme-seo' ); ?></label>
						<input type="url" id="sf-rank-url" name="url" class="sf-input" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
					</div>
					<div class="sf-field">
						<div class="sf-field__text">
							<label class="sf-field__label" for="sf-rank-position"><?php esc_html_e( 'Current position', 'seofyme-seo' ); ?></label>
						</div>
						<div class="sf-field__control">
							<input id="sf-rank-position" name="position" type="number" min="1" max="100" class="sf-input sf-input--small" value="10" />
						</div>
					</div>
				</div>
				<div class="sf-savebar">
					<button type="submit" class="sf-btn sf-btn--primary"><?php esc_html_e( 'Track keyword', 'seofyme-seo' ); ?></button>
				</div>
			</form>
		</section>
		<section class="sf-card">
			<header class="sf-card__header">
				<h2><?php esc_html_e( 'Tracked keywords', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Log new positions and review recent history.', 'seofyme-seo' ); ?></p>
			</header>
			<div class="sf-card__body sf-card__body--table">
				<table class="sf-table sf-table--data">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Keyword', 'seofyme-seo' ); ?></th>
							<th><?php esc_html_e( 'URL', 'seofyme-seo' ); ?></th>
							<th><?php esc_html_e( 'Position', 'seofyme-seo' ); ?></th>
							<th><?php esc_html_e( 'History', 'seofyme-seo' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'seofyme-seo' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td class="sf-table__empty" colspan="5"><?php esc_html_e( 'No keywords tracked yet.', 'seofyme-seo' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $i => $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['keyword'] ); ?></strong></td>
								<td><?php echo esc_html( $row['url'] ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sf-inline-form">
										<input type="hidden" name="action" value="seofyme_rank_update" />
										<input type="hidden" name="index" value="<?php echo esc_attr( (string) $i ); ?>" />
										<?php wp_nonce_field( 'seofyme_rank_update' ); ?>
										<input type="number" name="position" min="1" max="100" value="<?php echo esc_attr( (string) ( $row['position'] ?? '' ) ); ?>" class="sf-input sf-input--small" />
										<button type="submit" class="sf-btn sf-btn--secondary sf-btn--small"><?php esc_html_e( 'Log', 'seofyme-seo' ); ?></button>
									</form>
								</td>
								<td><code><?php echo esc_html( implode( ' → ', array_slice( array_column( $row['history'] ?? array(), 'position' ), -6 ) ) ); ?></code></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="seofyme_rank_delete" />
										<input type="hidden" name="index" value="<?php echo esc_attr( (string) $i ); ?>" />
										<?php wp_nonce_field( 'seofyme_rank_delete' ); ?>
										<button type="submit" class="sf-btn sf-btn--danger sf-btn--small"><?php esc_html_e( 'Delete', 'seofyme-seo' ); ?></button>
									</form>
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
	 * Add keyword.
	 *
	 * @return void
	 */
	public function handle_add() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_rank_add' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$rows   = $this->all();
		$pos    = isset( $_POST['position'] ) ? (int) $_POST['position'] : 0;
		$rows[] = array(
			'keyword'  => isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '',
			'url'      => isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : home_url( '/' ),
			'position' => $pos,
			'history'  => array( array( 'time' => time(), 'position' => $pos ) ),
		);
		$this->save( $rows );
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-ranks' ) );
		exit;
	}

	/**
	 * Update position.
	 *
	 * @return void
	 */
	public function handle_update() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_rank_update' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$i    = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;
		$pos  = isset( $_POST['position'] ) ? (int) $_POST['position'] : 0;
		$rows = $this->all();
		if ( isset( $rows[ $i ] ) ) {
			$rows[ $i ]['position'] = $pos;
			$rows[ $i ]['history'][] = array( 'time' => time(), 'position' => $pos );
			$rows[ $i ]['history']   = array_slice( $rows[ $i ]['history'], -50 );
			$this->save( $rows );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-ranks' ) );
		exit;
	}

	/**
	 * Delete.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_rank_delete' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$i    = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;
		$rows = $this->all();
		if ( isset( $rows[ $i ] ) ) {
			unset( $rows[ $i ] );
			$this->save( $rows );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-ranks' ) );
		exit;
	}
}
