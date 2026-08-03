<?php
/**
 * Redirects admin UI + CSV import.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin CRUD for redirects.
 */
class Seofyme_Redirect_Admin {

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
		add_action( 'admin_post_seofyme_add_redirect', [ $this, 'handle_add' ] );
		add_action( 'admin_post_seofyme_delete_redirect', [ $this, 'handle_delete' ] );
		add_action( 'admin_post_seofyme_import_redirects', [ $this, 'handle_import' ] );
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

		$redirects = $this->repo->all();
		?>
		<div class="wrap seofyme-redirects">
			<h1><?php esc_html_e( 'Redirect Manager', 'seofyme-seo' ); ?></h1>
			<p><?php esc_html_e( 'Keep old URLs working when you move or delete content. Import bulk redirects via CSV.', 'seofyme-seo' ); ?></p>

			<?php if ( isset( $_GET['seofyme_notice'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['seofyme_notice'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Add redirect', 'seofyme-seo' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="seofyme_add_redirect" />
				<?php wp_nonce_field( 'seofyme_add_redirect' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="origin"><?php esc_html_e( 'Old URL (path)', 'seofyme-seo' ); ?></label></th>
						<td><input type="text" class="regular-text" name="origin" id="origin" placeholder="/old-page" required /></td>
					</tr>
					<tr>
						<th><label for="target"><?php esc_html_e( 'New URL', 'seofyme-seo' ); ?></label></th>
						<td><input type="text" class="regular-text" name="target" id="target" placeholder="/new-page" required /></td>
					</tr>
					<tr>
						<th><label for="type"><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></label></th>
						<td>
							<select name="type" id="type">
								<option value="301">301</option>
								<option value="302">302</option>
								<option value="307">307</option>
								<option value="410">410</option>
								<option value="451">451</option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Add redirect', 'seofyme-seo' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Import CSV', 'seofyme-seo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'CSV format: origin,target,type (type optional, default 301)', 'seofyme-seo' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="seofyme_import_redirects" />
				<?php wp_nonce_field( 'seofyme_import_redirects' ); ?>
				<input type="file" name="csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import', 'seofyme-seo' ), 'secondary' ); ?>
			</form>

			<h2><?php esc_html_e( 'Existing redirects', 'seofyme-seo' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Origin', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Target', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'seofyme-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $redirects ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No redirects yet.', 'seofyme-seo' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $redirects as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( $row['origin'] ); ?></code></td>
								<td><?php echo esc_html( $row['target'] ); ?></td>
								<td><?php echo esc_html( (string) $row['type'] ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<input type="hidden" name="action" value="seofyme_delete_redirect" />
										<input type="hidden" name="id" value="<?php echo esc_attr( (string) $row['id'] ); ?>" />
										<?php wp_nonce_field( 'seofyme_delete_redirect' ); ?>
										<button type="submit" class="button-link-delete"><?php esc_html_e( 'Delete', 'seofyme-seo' ); ?></button>
									</form>
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
	 * Handle add.
	 *
	 * @return void
	 */
	public function handle_add() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_add_redirect' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$origin = isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$type   = isset( $_POST['type'] ) ? (int) $_POST['type'] : 301;
		$this->repo->create( $origin, $target, $type );
		$this->redirect_back( __( 'Redirect added.', 'seofyme-seo' ) );
	}

	/**
	 * Handle delete.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_delete_redirect' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$this->repo->delete( $id );
		$this->redirect_back( __( 'Redirect deleted.', 'seofyme-seo' ) );
	}

	/**
	 * Handle CSV import.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_import_redirects' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		if ( empty( $_FILES['csv']['tmp_name'] ) ) {
			$this->redirect_back( __( 'No file uploaded.', 'seofyme-seo' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$rows   = [];
		if ( $handle ) {
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$rows[] = $data;
			}
			fclose( $handle );
		}
		$count = $this->repo->import_csv_rows( $rows );
		$this->redirect_back( sprintf( /* translators: %d count */ __( 'Imported %d redirects.', 'seofyme-seo' ), $count ) );
	}

	/**
	 * Redirect back to redirects screen.
	 *
	 * @param string $notice Notice.
	 * @return void
	 */
	private function redirect_back( $notice ) {
		wp_safe_redirect(
			add_query_arg(
				'seofyme_notice',
				rawurlencode( $notice ),
				admin_url( 'admin.php?page=seofyme-redirects' )
			)
		);
		exit;
	}
}
