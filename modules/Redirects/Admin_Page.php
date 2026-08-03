<?php
/**
 * Redirects admin UI.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Redirects;

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
		?>
		<div class="wrap seofyme-wrap">
			<h1><?php esc_html_e( 'Redirects', 'seofyme-seo' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="seofyme_add_redirect" />
				<?php wp_nonce_field( 'seofyme_add_redirect' ); ?>
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Old path', 'seofyme-seo' ); ?></th><td><input name="origin" class="regular-text" placeholder="/old-url" required /></td></tr>
					<tr><th><?php esc_html_e( 'New URL', 'seofyme-seo' ); ?></th><td><input name="target" class="regular-text" placeholder="/new-url" required /></td></tr>
					<tr><th><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></th><td><select name="type"><option value="301">301</option><option value="302">302</option><option value="410">410</option></select></td></tr>
				</table>
				<?php submit_button( __( 'Add redirect', 'seofyme-seo' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Import CSV', 'seofyme-seo' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="seofyme_import_redirects" />
				<?php wp_nonce_field( 'seofyme_import_redirects' ); ?>
				<input type="file" name="csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import', 'seofyme-seo' ), 'secondary' ); ?>
			</form>

			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Origin', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Target', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Type', 'seofyme-seo' ); ?></th><th></th></tr></thead>
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
		</div>
		<?php
	}
}
