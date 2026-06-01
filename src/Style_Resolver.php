<?php
/**
 * Resolve which style variation applies to the current request.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

use ForWP\StyleSwitcher\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Priority: locked page style > explicit switch (query) > per-page style > visitor cookie > site default.
 */
final class Style_Resolver {

	public const VISITOR_COOKIE = 'forwp_ss_style';

	/**
	 * Resolved style per post id within the current request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $resolve_cache = array();

	/**
	 * Resolve style for a post context.
	 *
	 * @param int $post_id Post id (0 = current queried object).
	 * @return array{
	 *   slug: string,
	 *   source: string,
	 *   locked: bool,
	 *   show_switcher: bool,
	 *   page_style: string,
	 *   visitor_style: string
	 * }
	 */
	public static function resolve( int $post_id = 0 ): array {
		$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();

		if ( isset( self::$resolve_cache[ $post_id ] ) ) {
			return self::$resolve_cache[ $post_id ];
		}

		$page_style = $post_id > 0
			? sanitize_title( (string) get_post_meta( $post_id, Meta_Keys::PAGE_STYLE_SLUG, true ) )
			: '';

		$locked = $post_id > 0 && (bool) get_post_meta( $post_id, Meta_Keys::PAGE_STYLE_LOCKED, true );

		$query_style   = self::read_query_style();
		$cookie_style  = self::read_cookie_style();
		$visitor_style = '' !== $query_style ? $query_style : $cookie_style;
		$site_default  = Settings::instance()->get_default_variation();

		$slug   = '';
		$source = 'default';

		if ( $locked && '' !== $page_style && self::is_theme_variation_slug( $page_style ) ) {
			$slug   = $page_style;
			$source = 'page_locked';
		} elseif ( '' !== $query_style && self::is_allowed_slug( $query_style ) ) {
			$slug   = $query_style;
			$source = 'visitor_query';
		} elseif ( '' !== $page_style && self::is_theme_variation_slug( $page_style ) ) {
			$slug   = $page_style;
			$source = 'page';
		} elseif ( '' !== $cookie_style && self::is_allowed_slug( $cookie_style ) ) {
			$slug   = $cookie_style;
			$source = 'visitor';
		} elseif ( '' !== $site_default && self::is_allowed_slug( $site_default ) ) {
			$slug   = $site_default;
			$source = 'site_default';
		}

		$show_switcher = Settings::instance()->is_visitor_switcher_enabled()
			&& ! $locked
			&& Block_Theme_Guard::is_supported()
			&& ! empty( Style_Registry::get_variations() );

		self::$resolve_cache[ $post_id ] = array(
			'slug'           => $slug,
			'source'         => $source,
			'locked'         => $locked,
			'show_switcher'  => $show_switcher,
			'page_style'     => $page_style,
			'visitor_style'  => $visitor_style,
		);

		return self::$resolve_cache[ $post_id ];
	}

	/**
	 * Combined visitor preference (query param or cookie).
	 */
	public static function read_visitor_preference(): string {
		$query = self::read_query_style();
		if ( '' !== $query ) {
			return $query;
		}

		return self::read_cookie_style();
	}

	/**
	 * Style from ?forwp_ss_style= on the current request (explicit switch).
	 */
	public static function read_query_style(): string {
		if ( is_admin() || empty( $_GET[ self::VISITOR_COOKIE ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query_slug = sanitize_title( wp_unslash( (string) $_GET[ self::VISITOR_COOKIE ] ) );
		if ( '' !== $query_slug && self::is_allowed_slug( $query_slug ) ) {
			return $query_slug;
		}

		return '';
	}

	/**
	 * Persisted visitor cookie only.
	 */
	public static function read_cookie_style(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_COOKIE[ self::VISITOR_COOKIE ] ) ? wp_unslash( $_COOKIE[ self::VISITOR_COOKIE ] ) : '';

		return sanitize_title( (string) $value );
	}

	/**
	 * Whether slug exists in the active theme (any variation).
	 */
	public static function is_theme_variation_slug( string $slug ): bool {
		return Style_Registry::has_variation_raw( $slug );
	}

	/**
	 * Whether slug is allowed for visitor switching / site default.
	 */
	public static function is_allowed_slug( string $slug ): bool {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return false;
		}

		if ( ! in_array( $slug, Settings::instance()->get_allowed_variation_slugs(), true ) ) {
			return false;
		}

		return Style_Registry::has_variation_raw( $slug );
	}

	/**
	 * @deprecated Use is_theme_variation_slug() or is_allowed_slug().
	 */
	public static function is_valid_slug( string $slug ): bool {
		return self::is_allowed_slug( $slug );
	}
}
