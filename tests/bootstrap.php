<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

$root = dirname( __DIR__ );

if ( file_exists( $root . '/vendor/autoload.php' ) ) {
	require_once $root . '/vendor/autoload.php';
} else {
	require_once $root . '/src/Autoload.php';
	ForWP\StyleSwitcher\Autoload::register();
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Minimal stub for unit tests.
	 *
	 * @param string $title Title.
	 */
	function sanitize_title( $title ) {
		$title = strtolower( (string) $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );

		return trim( (string) $title, '-' );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		return $value;
	}
}
