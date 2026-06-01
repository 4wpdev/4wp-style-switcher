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
 * Priority: locked page style > visitor preference > per-page style > site default.
 */
final class Style_Resolver {

	public const VISITOR_COOKIE = 'forwp_ss_style';

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

		$page_style = $post_id > 0
			? sanitize_title( (string) get_post_meta( $post_id, Meta_Keys::PAGE_STYLE_SLUG, true ) )
			: '';

		$locked = $post_id > 0 && (bool) get_post_meta( $post_id, Meta_Keys::PAGE_STYLE_LOCKED, true );

		$visitor_style = self::read_visitor_preference();
		$site_default  = Settings::instance()->get_default_variation();

		$slug   = '';
		$source = 'default';

		if ( $locked && '' !== $page_style && self::is_theme_variation_slug( $page_style ) ) {
			$slug   = $page_style;
			$source = 'page_locked';
		} elseif ( '' !== $visitor_style && self::is_allowed_slug( $visitor_style ) ) {
			$slug   = $visitor_style;
			$source = 'visitor';
		} elseif ( '' !== $page_style && self::is_theme_variation_slug( $page_style ) ) {
			$slug   = $page_style;
			$source = 'page';
		} elseif ( '' !== $site_default && self::is_allowed_slug( $site_default ) ) {
			$slug   = $site_default;
			$source = 'site_default';
		}

		$show_switcher = Settings::instance()->is_visitor_switcher_enabled()
			&& ! $locked
			&& Block_Theme_Guard::is_supported()
			&& ! empty( Style_Registry::get_variations() );

		return array(
			'slug'           => $slug,
			'source'         => $source,
			'locked'         => $locked,
			'show_switcher'  => $show_switcher,
			'page_style'     => $page_style,
			'visitor_style'  => $visitor_style,
		);
	}

	/**
	 * Read visitor preference from cookie.
	 */
	public static function read_visitor_preference(): string {
		if ( ! is_admin() && ! empty( $_GET[ self::VISITOR_COOKIE ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_slug = sanitize_title( wp_unslash( (string) $_GET[ self::VISITOR_COOKIE ] ) );
			if ( '' !== $query_slug && Style_Resolver::is_allowed_slug( $query_slug ) ) {
				return $query_slug;
			}
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = isset( $_COOKIE[ self::VISITOR_COOKIE ] ) ? wp_unslash( $_COOKIE[ self::VISITOR_COOKIE ] ) : '';

		return sanitize_title( (string) $value );
	}

	/**
	 * Whether slug exists in the active theme (any variation).
	 */
	public static function is_theme_variation_slug( string $slug ): bool {
		return null !== Style_Registry::get_variation_raw( $slug );
	}

	/**
	 * Whether slug is allowed for visitor switching / site default.
	 */
	public static function is_allowed_slug( string $slug ): bool {
		return null !== Style_Registry::get_variation( $slug );
	}

	/**
	 * @deprecated Use is_theme_variation_slug() or is_allowed_slug().
	 */
	public static function is_valid_slug( string $slug ): bool {
		return self::is_allowed_slug( $slug );
	}
}
