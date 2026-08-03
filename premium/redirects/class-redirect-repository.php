<?php
/**
 * Redirect storage.
 *
 * @package Seofyme\SEO\Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD for redirects table.
 */
class Seofyme_Redirect_Repository {

	public const TABLE = 'seofyme_redirects';

	/**
	 * Create DB table.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			origin varchar(512) NOT NULL,
			target varchar(512) NOT NULL,
			type smallint(3) NOT NULL DEFAULT 301,
			format varchar(20) NOT NULL DEFAULT 'plain',
			enabled tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY origin (origin(191)),
			KEY enabled (enabled)
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
	 * List redirects.
	 *
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function all( $limit = 200, $offset = 0 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} ORDER BY id DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Find by origin path.
	 *
	 * @param string $origin Origin path.
	 * @return array|null
	 */
	public function find_by_origin( $origin ) {
		global $wpdb;
		$origin = $this->normalize_origin( $origin );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} WHERE origin = %s AND enabled = 1 LIMIT 1",
				$origin
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Insert redirect.
	 *
	 * @param string $origin Origin.
	 * @param string $target Target.
	 * @param int    $type Type.
	 * @param string $format Format.
	 * @return int|false
	 */
	public function create( $origin, $target, $type = 301, $format = 'plain' ) {
		global $wpdb;
		$origin = $this->normalize_origin( $origin );
		$target = esc_url_raw( $target );
		$type   = in_array( (int) $type, [ 301, 302, 307, 410, 451 ], true ) ? (int) $type : 301;

		$result = $wpdb->insert(
			$this->table(),
			[
				'origin' => $origin,
				'target' => $target,
				'type'   => $type,
				'format' => sanitize_key( $format ),
			],
			[ '%s', '%s', '%d', '%s' ]
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Delete redirect.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( $this->table(), [ 'id' => (int) $id ], [ '%d' ] );
	}

	/**
	 * Import from CSV rows [origin, target, type].
	 *
	 * @param array $rows Rows.
	 * @return int Created count.
	 */
	public function import_csv_rows( array $rows ) {
		$count = 0;
		foreach ( $rows as $row ) {
			if ( count( $row ) < 2 ) {
				continue;
			}
			$origin = trim( (string) $row[0] );
			$target = trim( (string) $row[1] );
			$type   = isset( $row[2] ) ? (int) $row[2] : 301;
			if ( $origin && $target && $this->create( $origin, $target, $type ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Normalize origin to path starting with /.
	 *
	 * @param string $origin Origin.
	 * @return string
	 */
	public function normalize_origin( $origin ) {
		$origin = trim( (string) $origin );
		if ( preg_match( '#^https?://#i', $origin ) ) {
			$path = wp_parse_url( $origin, PHP_URL_PATH );
			$origin = $path ? $path : '/';
		}
		if ( $origin === '' || $origin[0] !== '/' ) {
			$origin = '/' . ltrim( $origin, '/' );
		}
		return untrailingslashit( $origin ) ?: '/';
	}
}
