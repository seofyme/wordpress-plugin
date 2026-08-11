<?php
/**
 * Redirects admin UI.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Redirects;

use SeofymeSEO\Admin\Page_Shell;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders redirect manager.
 */
class Admin_Page {

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$repo      = new Redirects();
		$redirects = $repo->all();
		Page_Shell::open(
			__( 'Redirects', 'seofyme-seo' ),
			__( 'Keep old URLs working when content moves — add one, or import a CSV.', 'seofyme-seo' )
		);
		?>
			<section class="seofyme-panel">
				<h2><?php esc_html_e( 'Add redirect', 'seofyme-seo' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="seofyme_add_redirect" />
					<?php wp_nonce_field( 'seofyme_add_redirect' ); ?>
					<table class="form-table">
						<tr><th><?php esc_html_e( 'Old path / pattern', 'seofyme-seo' ); ?></th><td><input type="text" name="origin" class="regular-text" placeholder="/old-url or ^/blog/(.*)$" required /></td></tr>
						<tr><th><?php esc_html_e( 'New URL', 'seofyme-seo' ); ?></th><td><input type="text" name="target" class="regular-text" placeholder="/new-url or /news/$1" required /></td></tr>
						<tr><th><?php esc_html_e( 'Format', 'seofyme-seo' ); ?></th><td><select name="format"><option value="plain">plain</option><option value="regex">regex</option></select></td></tr>
						<tr><th><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></th><td><select name="type"><option value="301">301</option><option value="302">302</option><option value="410">410</option></select></td></tr>
					</table>
					<?php submit_button( __( 'Add redirect', 'seofyme-seo' ) ); ?>
				</form>
			</section>

			<section class="seofyme-panel">
				<h2><?php esc_html_e( 'Import CSV', 'seofyme-seo' ); ?></h2>
				<p><?php esc_html_e( 'Format: origin, target, type (type optional).', 'seofyme-seo' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="seofyme_import_redirects" />
					<?php wp_nonce_field( 'seofyme_import_redirects' ); ?>
					<p><input type="file" name="csv" accept=".csv,text/csv" required /></p>
					<?php submit_button( __( 'Import', 'seofyme-seo' ), 'secondary' ); ?>
				</form>
			</section>

			<section class="seofyme-panel">
				<h2><?php esc_html_e( 'Existing redirects', 'seofyme-seo' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Origin', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Target', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Format', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php if ( empty( $redirects ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No redirects yet.', 'seofyme-seo' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $redirects as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( $row['origin'] ); ?></code></td>
								<td><?php echo esc_html( $row['target'] ); ?></td>
								<td><?php echo esc_html( $row['format'] ?? 'plain' ); ?></td>
								<td><?php echo esc_html( (string) $row['type'] ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="seofyme_delete_redirect" />
										<input type="hidden" name="id" value="<?php echo esc_attr( (string) $row['id'] ); ?>" />
										<?php wp_nonce_field( 'seofyme_delete_redirect' ); ?>
										<button class="button-link-delete"><?php esc_html_e( 'Delete', 'seofyme-seo' ); ?></button>
									</form>
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
}
