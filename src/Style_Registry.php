<?php
/**
 * Style variations from the active block theme.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

use ForWP\StyleSwitcher\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Reads theme.json style variations via core APIs.
 */
final class Style_Registry {

	/**
	 * All style variations from the active theme.
	 *
	 * @return array<int, array{slug: string, title: string}>
	 */
	public static function get_theme_variations(): array {
		if ( ! Block_Theme_Guard::is_supported() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			return array();
		}

		$raw = self::get_raw_theme_variations();
		$out = array();

		foreach ( $raw as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			$slug = self::variation_slug( $variation );
			if ( '' === $slug ) {
				continue;
			}

			$title = isset( $variation['title'] ) ? (string) $variation['title'] : $slug;

			$out[] = array(
				'slug'  => $slug,
				'title' => $title,
			);
		}

		/**
		 * Filter registered style variations exposed by the plugin.
		 *
		 * @param array<int, array{slug: string, title: string}> $out Variations.
		 * @param array<int, array<string, mixed>>              $raw Raw theme data.
		 */
		return apply_filters( 'forwp_style_switcher_variations', $out, $raw );
	}

	/**
	 * Variations allowed in admin settings (subset of theme variations).
	 *
	 * @return array<int, array{slug: string, title: string}>
	 */
	public static function get_variations(): array {
		$allowed = Settings::instance()->get_allowed_variation_slugs();
		if ( empty( $allowed ) ) {
			return array();
		}

		$out = array();
		foreach ( self::get_theme_variations() as $variation ) {
			if ( in_array( $variation['slug'], $allowed, true ) ) {
				$out[] = $variation;
			}
		}

		return $out;
	}

	/**
	 * Full variation data from the theme (settings, styles, etc.).
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_variation_raw( string $slug ): ?array {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}

		if ( ! Block_Theme_Guard::is_supported() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			return null;
		}

		foreach ( self::get_raw_theme_variations() as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			if ( self::variation_slug( $variation ) === $slug ) {
				return $variation;
			}
		}

		return null;
	}

	/**
	 * Find one variation by slug.
	 *
	 * @return array{slug: string, title: string}|null
	 */
	public static function get_variation( string $slug ): ?array {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}

		foreach ( self::get_variations() as $variation ) {
			if ( $variation['slug'] === $slug ) {
				return $variation;
			}
		}

		return null;
	}

	/**
	 * Raw theme variations from core, deduplicated by slug.
	 *
	 * WordPress scans /styles/ recursively, so themes like Twenty Twenty-Five
	 * expose the same slug twice (e.g. styles/01-evening.json and styles/colors/01-evening.json).
	 * Core sorts paths with ksort(), so root-level files win when we keep the first slug.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_raw_theme_variations(): array {
		return self::dedupe_variations_by_slug( \WP_Theme_JSON_Resolver::get_style_variations( 'theme' ) );
	}

	/**
	 * @param array<int, mixed> $variations Raw variations from WP_Theme_JSON_Resolver.
	 * @return array<int, array<string, mixed>>
	 */
	private static function dedupe_variations_by_slug( array $variations ): array {
		$seen = array();
		$out  = array();

		foreach ( $variations as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			$slug = self::variation_slug( $variation );
			if ( '' === $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}

			$seen[ $slug ] = true;
			$out[]         = $variation;
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $variation Raw variation from WP_Theme_JSON_Resolver.
	 */
	private static function variation_slug( array $variation ): string {
		if ( ! empty( $variation['slug'] ) ) {
			return sanitize_title( (string) $variation['slug'] );
		}

		if ( ! empty( $variation['title'] ) ) {
			return sanitize_title( (string) $variation['title'] );
		}

		return '';
	}
}
