<?php
/**
 * 404 monitor — log misses and turn them into redirects.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\NotFound;

use SeofymeSEO\Admin\Page_Shell;
use SeofymeSEO\Modules\Redirects\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks 404 URLs.
 */
class NotFoundMonitor {

	public const TABLE = 'seofyme_404';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'capture' ), 99 );
		add_action( 'admin_post_seofyme_404_redirect', array( $this, 'make_redirect' ) );
		add_action( 'admin_post_seofyme_404_clear', array( $this, 'clear' ) );
	}

	/**
	 * Install table.
	 *
	 * @return void
	 */
	public static function install_table() {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url varchar(512) NOT NULL,
			hits bigint(20) unsigned NOT NULL DEFAULT 1,
			last_seen datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY url (url(191))
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Capture 404.
	 *
	 * @return void
	 */
	public function capture() {
		if ( ! is_404() || is_admin() ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			return;
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO `' . esc_sql( $wpdb->prefix . self::TABLE ) . '` (url, hits, last_seen) VALUES (%s, 1, %s)
				ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen)',
				$path,
				$now
			)
		);
	}

	/**
	 * List rows.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function all( $limit = 100 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read.
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $wpdb->prefix . self::TABLE ) . '` ORDER BY hits DESC, last_seen DESC LIMIT %d',
				$limit
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * Admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$rows = $this->all();
		Page_Shell::open( __( '404 monitor', 'seofyme-seo' ), __( 'See broken URLs visitors hit, then convert them into redirects.', 'seofyme-seo' ) );
		?>
		<section class="seofyme-panel">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1rem">
				<input type="hidden" name="action" value="seofyme_404_clear" />
				<?php wp_nonce_field( 'seofyme_404_clear' ); ?>
				<?php submit_button( __( 'Clear log', 'seofyme-seo' ), 'secondary', 'submit', false ); ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'URL', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Hits', 'seofyme-seo' ); ?></th><th><?php esc_html_e( 'Last seen', 'seofyme-seo' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No 404s logged yet.', 'seofyme-seo' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row['url'] ); ?></code></td>
							<td><?php echo esc_html( (string) $row['hits'] ); ?></td>
							<td><?php echo esc_html( $row['last_seen'] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="seofyme-inline-form">
									<input type="hidden" name="action" value="seofyme_404_redirect" />
									<input type="hidden" name="origin" value="<?php echo esc_attr( $row['url'] ); ?>" />
									<?php wp_nonce_field( 'seofyme_404_redirect' ); ?>
									<input type="text" name="target" class="regular-text" placeholder="/" required />
									<button class="button button-primary"><?php esc_html_e( 'Redirect', 'seofyme-seo' ); ?></button>
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

	/**
	 * Create redirect from 404.
	 *
	 * @return void
	 */
	public function make_redirect() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_404_redirect' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$origin = isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '/';
		( new Redirects() )->create( $origin, $target, 301, 'plain' );
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-404' ) );
		exit;
	}

	/**
	 * Clear log.
	 *
	 * @return void
	 */
	public function clear() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'seofyme_404_clear' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Table name cannot use placeholders.
		$wpdb->query( 'TRUNCATE TABLE `' . esc_sql( $wpdb->prefix . self::TABLE ) . '`' );
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-404' ) );
		exit;
	}
}
