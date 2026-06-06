<?php
/**
 * Visitor preference storage (localStorage + cookie sync).
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Frontend;

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Style_Resolver;

defined( 'ABSPATH' ) || exit;

/**
 * Client storage config and early cookie sync from localStorage.
 */
final class Visitor_Storage {

	public static function boot(): void {
		add_action( 'init', array( self::class, 'handle_query_switch' ), 0 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_head_sync' ), 0 );
	}

	/**
	 * Apply style from ?forwp_ss_style= query (works without JS inside Navigation).
	 */
	public static function handle_query_switch(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public visitor style preference; slug is sanitized below.
		if ( is_admin() || empty( $_GET[ Style_Resolver::VISITOR_COOKIE ] ) ) {
			return;
		}

		if ( ! Settings::instance()->is_visitor_switcher_enabled() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_title( wp_unslash( (string) $_GET[ Style_Resolver::VISITOR_COOKIE ] ) );
		if ( '' === $slug || ! Style_Resolver::is_allowed_slug( $slug ) ) {
			return;
		}

		$days = Settings::instance()->get_visitor_storage_days();
		setcookie(
			Style_Resolver::VISITOR_COOKIE,
			$slug,
			time() + ( $days * DAY_IN_SECONDS ),
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			false
		);

		$_COOKIE[ Style_Resolver::VISITOR_COOKIE ] = $slug;
	}

	/**
	 * Current URL without the style query argument.
	 */
	private static function get_redirect_url_after_switch(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$request_uri = sanitize_text_field( $request_uri );
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$query       = wp_parse_url( $request_uri, PHP_URL_QUERY );
		$args        = array();

		if ( is_string( $query ) && '' !== $query ) {
			parse_str( $query, $args );
		}

		unset( $args[ Style_Resolver::VISITOR_COOKIE ] );

		$url = home_url( is_string( $path ) && '' !== $path ? $path : '/' );
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Frontend URL that switches visitor style (no-JS fallback).
	 */
	public static function get_switch_url( string $slug ): string {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return '';
		}

		return add_query_arg( Style_Resolver::VISITOR_COOKIE, $slug );
	}

	public static function register_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_register_script(
			'forwp-ss-visitor-storage-head',
			FORWP_STYLE_SWITCHER_URL . 'assets/visitor-storage-head-sync.js',
			array(),
			FORWP_STYLE_SWITCHER_VERSION,
			false
		);

		wp_register_script(
			'forwp-ss-visitor-storage',
			FORWP_STYLE_SWITCHER_URL . 'assets/visitor-storage.js',
			array(),
			FORWP_STYLE_SWITCHER_VERSION,
			true
		);
	}

	/**
	 * Early head sync so server-side style matches localStorage before paint.
	 */
	public static function enqueue_head_sync(): void {
		if ( is_admin() ) {
			return;
		}

		self::register_assets();
		wp_enqueue_script( 'forwp-ss-visitor-storage-head' );
		wp_add_inline_script(
			'forwp-ss-visitor-storage-head',
			'window.forwpSsVisitorStorageConfig = ' . wp_json_encode( self::get_client_config() ) . ';',
			'before'
		);
	}

	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'forwp-ss-visitor-storage' );
		wp_localize_script( 'forwp-ss-visitor-storage', 'forwpSsVisitorStorageConfig', self::get_client_config() );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_client_config(): array {
		return array(
			'storageKey'  => Style_Resolver::VISITOR_COOKIE,
			'cookieName'  => Style_Resolver::VISITOR_COOKIE,
			'storageDays' => Settings::instance()->get_visitor_storage_days(),
		);
	}
}
