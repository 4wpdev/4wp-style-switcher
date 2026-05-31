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
		add_action( 'wp_head', array( self::class, 'print_head_sync' ), 0 );
	}

	/**
	 * Apply style from ?forwp_ss_style= query (works without JS inside Navigation).
	 */
	public static function handle_query_switch(): void {
		if ( is_admin() || empty( $_GET[ Style_Resolver::VISITOR_COOKIE ] ) ) {
			return;
		}

		if ( ! Settings::instance()->is_visitor_switcher_enabled() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_title( wp_unslash( (string) $_GET[ Style_Resolver::VISITOR_COOKIE ] ) );
		if ( '' === $slug || ! Style_Resolver::is_valid_slug( $slug ) ) {
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
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
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
			'forwp-ss-visitor-storage',
			FORWP_STYLE_SWITCHER_URL . 'assets/visitor-storage.js',
			array(),
			FORWP_STYLE_SWITCHER_VERSION,
			true
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

	public static function print_head_sync(): void {
		if ( is_admin() ) {
			return;
		}

		$config = wp_json_encode( self::get_client_config() );
		if ( ! $config ) {
			return;
		}

		printf(
			'<script>(function(c){if(!c||!window.localStorage)return;try{var key=c.storageKey||c.cookieName,name=c.cookieName||key,days=parseInt(c.storageDays,10)||365,expiresAt=Date.now()+days*86400000,secure=window.location.protocol==="https:"?"; Secure":"",match=document.cookie.match(new RegExp("(?:^|; )"+name.replace(/([.$?*|{}()\\[\\]\\\\/+^])/g,"\\\\$1")+"=([^;]*)")),cookieSlug=match?decodeURIComponent(match[1]):"",raw=localStorage.getItem(key),lsData=raw?JSON.parse(raw):null,lsSlug="";if(lsData&&lsData.slug){if(!lsData.expires||Date.now()<=lsData.expires){lsSlug=lsData.slug;}else{localStorage.removeItem(key);}}if(cookieSlug){if(cookieSlug!==lsSlug){localStorage.setItem(key,JSON.stringify({slug:cookieSlug,expires:expiresAt}));}return;}if(lsSlug){document.cookie=name+"="+encodeURIComponent(lsSlug)+"; path=/; SameSite=Lax"+secure;}catch(e){}})(%s);</script>' . "\n",
			$config
		);
	}
}
