<?php
/**
 * PSR-4 autoloader fallback when Composer vendor is absent.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

defined( 'ABSPATH' ) || exit;

/**
 * Registers autoload for ForWP\StyleSwitcher namespace.
 */
final class Autoload {

	/**
	 * Register spl autoload.
	 */
	public static function register(): void {
		spl_autoload_register(
			static function ( string $class_name ): void {
				$prefix = __NAMESPACE__ . '\\';
				if ( strncmp( $prefix, $class_name, strlen( $prefix ) ) !== 0 ) {
					return;
				}
				$relative = substr( $class_name, strlen( $prefix ) );
				$file     = FORWP_STYLE_SWITCHER_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
