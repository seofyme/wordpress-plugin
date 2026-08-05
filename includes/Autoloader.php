<?php
/**
 * PSR-4 style autoloader for SeofymeSEO.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps SeofymeSEO\* to includes/ and modules/.
 */
class Autoloader {

	/**
	 * Base path.
	 *
	 * @var string
	 */
	private static $base;

	/**
	 * Register autoloader.
	 *
	 * @param string $base Plugin path.
	 * @return void
	 */
	public static function register( $base ) {
		self::$base = trailingslashit( $base );
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load class file.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function load( $class ) {
		if ( strpos( $class, 'SeofymeSEO\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'SeofymeSEO\\' ) );
		$relative = str_replace( '\\', '/', $relative );

		$candidates = array(
			self::$base . 'includes/' . $relative . '.php',
		);

		// SeofymeSEO\Modules\Foo\Bar → modules/Foo/Bar.php (not modules/Modules/Foo/Bar.php).
		if ( strpos( $relative, 'Modules/' ) === 0 ) {
			$candidates[] = self::$base . 'modules/' . substr( $relative, strlen( 'Modules/' ) ) . '.php';
		} else {
			$candidates[] = self::$base . 'modules/' . $relative . '.php';
		}

		foreach ( $candidates as $file ) {
			if ( is_readable( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
}
