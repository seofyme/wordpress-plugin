<?php
/**
 * Redirect runtime + schema install.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies redirects and watches slug changes.
 */
class Redirects {

	public const TABLE = 'seofyme_redirects';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
		add_action( 'post_updated', array( $this, 'on_post_updated' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'wp_ajax_seofyme_create_suggested_redirect', array( $this, 'ajax_create' ) );
		add_action( 'admin_post_seofyme_add_redirect', array( $this, 'handle_add' ) );
		add_action( 'admin_post_seofyme_delete_redirect', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_seofyme_import_redirects', array( $this, 'handle_import' ) );
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
			origin varchar(512) NOT NULL,
			target varchar(512) NOT NULL,
			type smallint(3) NOT NULL DEFAULT 301,
			format varchar(20) NOT NULL DEFAULT 'plain',
			enabled tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY origin (origin(191)),
			KEY format (format)
		) {$charset};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Normalize origin path.
	 *
	 * @param string $origin Origin.
	 * @return string
	 */
	public function normalize( $origin ) {
		$origin = trim( (string) $origin );
		if ( preg_match( '#^https?://#i', $origin ) ) {
			$path   = wp_parse_url( $origin, PHP_URL_PATH );
			$origin = $path ? $path : '/';
		}
		if ( '' === $origin || '/' !== $origin[0] ) {
			$origin = '/' . ltrim( $origin, '/' );
		}
		return untrailingslashit( $origin ) ?: '/';
	}

	/**
	 * Create redirect.
	 *
	 * @param string $origin Origin or regex.
	 * @param string $target Target.
	 * @param int    $type Type.
	 * @param string $format plain|regex.
	 * @return int|false
	 */
	public function create( $origin, $target, $type = 301, $format = 'plain' ) {
		global $wpdb;
		$type   = in_array( (int) $type, array( 301, 302, 307, 410, 451 ), true ) ? (int) $type : 301;
		$format = ( 'regex' === $format ) ? 'regex' : 'plain';
		$origin = ( 'regex' === $format ) ? trim( (string) $origin ) : $this->normalize( $origin );
		// Custom table — $wpdb helpers are required.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ok = $wpdb->insert(
			$this->table(),
			array(
				'origin' => $origin,
				'target' => ( 0 === strpos( $target, 'http' ) || false !== strpos( $target, '$' ) ) ? $target : esc_url_raw( $target ),
				'type'   => $type,
				'format' => $format,
			),
			array( '%s', '%s', '%d', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->delete( $this->table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * All redirects.
	 *
	 * @return array
	 */
	public function all() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read.
		return $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $this->table() ) . '` ORDER BY id DESC LIMIT 500', ARRAY_A ) ?: array();
	}

	/**
	 * Find enabled redirect (plain exact, then regex).
	 *
	 * @param string $path Path.
	 * @return array|null
	 */
	public function find( $path ) {
		global $wpdb;
		$normalized = $this->normalize( $path );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $this->table() ) . '` WHERE origin = %s AND enabled = 1 AND format = %s LIMIT 1',
				$normalized,
				'plain'
			),
			ARRAY_A
		);
		if ( $row ) {
			return $row;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read.
		$regex_rows = $wpdb->get_results( 'SELECT * FROM `' . esc_sql( $this->table() ) . '` WHERE enabled = 1 AND format = \'regex\' ORDER BY id ASC', ARRAY_A ) ?: array();

		foreach ( $regex_rows as $candidate ) {
			$pattern = '#' . str_replace( '#', '\\#', $candidate['origin'] ) . '#';
			if ( @preg_match( $pattern, $normalized ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$candidate['_match_path'] = $normalized;
				return $candidate;
			}
		}
		return null;
	}

	/**
	 * Execute redirect.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		if ( is_admin() ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		$row  = $this->find( is_string( $path ) ? $path : '/' );
		if ( ! $row ) {
			return;
		}
		$type = absint( $row['type'] );
		if ( 451 === $type ) {
			status_header( 451 );
			wp_die( esc_html__( 'This content is no longer available.', 'seofyme-seo' ), '', array( 'response' => 451 ) );
		}
		if ( 410 === $type ) {
			status_header( 410 );
			wp_die( esc_html__( 'This content is no longer available.', 'seofyme-seo' ), '', array( 'response' => 410 ) );
		}
		$target = $row['target'];
		if ( 'regex' === ( $row['format'] ?? 'plain' ) && ! empty( $row['_match_path'] ) ) {
			$pattern = '#' . str_replace( '#', '\\#', $row['origin'] ) . '#';
			$replaced = @preg_replace( $pattern, $target, $row['_match_path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_string( $replaced ) && '' !== $replaced ) {
				$target = $replaced;
			}
		}
		if ( 0 !== strpos( $target, 'http' ) ) {
			$target = home_url( $target );
		}
		wp_redirect( $target, $type ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Slug change watcher.
	 *
	 * @param int      $post_id ID.
	 * @param \WP_Post $after After.
	 * @param \WP_Post $before Before.
	 * @return void
	 */
	public function on_post_updated( $post_id, $after, $before ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== $before->post_status || $after->post_name === $before->post_name ) {
			return;
		}
		$old = wp_parse_url( get_permalink( $before ), PHP_URL_PATH );
		$new = get_permalink( $after );
		if ( ! $old || ! $new ) {
			return;
		}
		$key  = 'seofyme_redir_suggest_' . get_current_user_id();
		$list = get_transient( $key );
		if ( ! is_array( $list ) ) {
			$list = array();
		}
		$list[] = array( 'origin' => $old, 'target' => $new );
		set_transient( $key, $list, HOUR_IN_SECONDS );
	}

	/**
	 * Admin notice.
	 *
	 * @return void
	 */
	public function notice() {
		$list = get_transient( 'seofyme_redir_suggest_' . get_current_user_id() );
		if ( empty( $list ) || ! is_array( $list ) ) {
			return;
		}
		foreach ( $list as $i => $item ) {
			printf(
				'<div class="notice notice-warning"><p>%s <button type="button" class="button button-primary seofyme-create-redirect" data-origin="%s" data-target="%s" data-index="%d">%s</button></p></div>',
				esc_html__( 'Seofyme detected a URL change. Create a 301 redirect?', 'seofyme-seo' ),
				esc_attr( $item['origin'] ),
				esc_attr( $item['target'] ),
				(int) $i,
				esc_html__( 'Create redirect', 'seofyme-seo' )
			);
		}
	}

	/**
	 * AJAX create.
	 *
	 * @return void
	 */
	public function ajax_create() {
		check_ajax_referer( 'seofyme_seo', 'nonce' );
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_send_json_error( null, 403 );
		}
		$origin = isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '';
		$id     = $this->create( $origin, $target, 301 );
		wp_send_json_success( array( 'id' => $id ) );
	}

	/**
	 * Form handlers.
	 *
	 * @return void
	 */
	public function handle_add() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_add_redirect' ) ) {
			wp_die( 'Unauthorized' );
		}
		$this->create(
			isset( $_POST['origin'] ) ? sanitize_text_field( wp_unslash( $_POST['origin'] ) ) : '',
			isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '',
			isset( $_POST['type'] ) ? (int) $_POST['type'] : 301,
			isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'plain'
		);
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-redirects&notice=added' ) );
		exit;
	}

	/**
	 * Delete handler.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_delete_redirect' ) ) {
			wp_die( 'Unauthorized' );
		}
		$this->delete( isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-redirects&notice=deleted' ) );
		exit;
	}

	/**
	 * CSV import.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'seofyme_import_redirects' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'seofyme-seo' ) );
		}
		$count = 0;
		if ( ! empty( $_FILES['csv']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				WP_Filesystem();
			}

			$tmp = wp_normalize_path( (string) $_FILES['csv']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw = ( $wp_filesystem && is_callable( array( $wp_filesystem, 'get_contents' ) ) )
				? $wp_filesystem->get_contents( $tmp )
				: false;

			if ( is_string( $raw ) && '' !== $raw ) {
				$lines = preg_split( '/\r\n|\r|\n/', $raw );
				foreach ( (array) $lines as $line ) {
					$row = str_getcsv( $line );
					if ( count( $row ) < 2 ) {
						continue;
					}
					if ( $this->create( $row[0], $row[1], isset( $row[2] ) ? (int) $row[2] : 301 ) ) {
						++$count;
					}
				}
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=seofyme-seo-redirects&notice=imported&count=' . $count ) );
		exit;
	}
}
