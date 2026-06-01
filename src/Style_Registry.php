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
	 * Deduped raw variations indexed by slug (one load per request).
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $raw_by_slug = null;

	/**
	 * @var array<int, array{slug: string, title: string}>|null
	 */
	private static $theme_variations = null;

	/**
	 * @var array<int, array{slug: string, title: string}>|null
	 */
	private static $allowed_variations = null;

	/**
	 * All style variations from the active theme.
	 *
	 * @return array<int, array{slug: string, title: string}>
	 */
	public static function get_theme_variations(): array {
		if ( null !== self::$theme_variations ) {
			return self::$theme_variations;
		}

		if ( ! Block_Theme_Guard::is_supported() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			self::$theme_variations = array();
			return self::$theme_variations;
		}

		self::ensure_raw_index();

		$out = array();
		foreach ( self::$raw_by_slug as $variation ) {
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
		self::$theme_variations = apply_filters( 'forwp_style_switcher_variations', $out, array_values( self::$raw_by_slug ) );

		return self::$theme_variations;
	}

	/**
	 * Variations allowed in admin settings (subset of theme variations).
	 *
	 * @return array<int, array{slug: string, title: string}>
	 */
	public static function get_variations(): array {
		if ( null !== self::$allowed_variations ) {
			return self::$allowed_variations;
		}

		$allowed = Settings::instance()->get_allowed_variation_slugs();
		if ( empty( $allowed ) ) {
			self::$allowed_variations = array();
			return self::$allowed_variations;
		}

		$allowed_set = array_fill_keys( $allowed, true );
		$out         = array();

		foreach ( self::get_theme_variations() as $variation ) {
			if ( isset( $allowed_set[ $variation['slug'] ] ) ) {
				$out[] = $variation;
			}
		}

		self::$allowed_variations = $out;

		return self::$allowed_variations;
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

		if ( ! self::has_variation_raw( $slug ) ) {
			return null;
		}

		self::ensure_raw_index();

		return self::$raw_by_slug[ $slug ] ?? null;
	}

	/**
	 * Whether a slug exists in the active theme (O(1) after first load).
	 */
	public static function has_variation_raw( string $slug ): bool {
		$slug = sanitize_title( $slug );
		if ( '' === $slug || ! Block_Theme_Guard::is_supported() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			return false;
		}

		self::ensure_raw_index();

		return isset( self::$raw_by_slug[ $slug ] );
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
	 * Build the slug index once per request.
	 */
	private static function ensure_raw_index(): void {
		if ( null !== self::$raw_by_slug ) {
			return;
		}

		self::$raw_by_slug = array();

		if ( ! Block_Theme_Guard::is_supported() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			return;
		}

		foreach ( self::dedupe_variations_by_slug( \WP_Theme_JSON_Resolver::get_style_variations( 'theme' ) ) as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			$slug = self::variation_slug( $variation );
			if ( '' === $slug ) {
				continue;
			}

			self::$raw_by_slug[ $slug ] = $variation;
		}
	}

	/**
	 * @param array<int, mixed> $variations Raw variations from WP_Theme_JSON_Resolver.
	 * @return array<int, array<string, mixed>>
	 */
	private static function dedupe_variations_by_slug( array $variations ): array {
		$by_slug = array();

		foreach ( $variations as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}

			$slug = self::variation_slug( $variation );
			if ( '' === $slug ) {
				continue;
			}

			if ( ! isset( $by_slug[ $slug ] ) || self::variation_weight( $variation ) > self::variation_weight( $by_slug[ $slug ] ) ) {
				$by_slug[ $slug ] = $variation;
			}
		}

		return array_values( $by_slug );
	}

	/**
	 * Prefer full /styles/*.json variations over /styles/colors/* partials.
	 *
	 * @param array<string, mixed> $variation Raw variation from core.
	 */
	private static function variation_weight( array $variation ): int {
		$weight = count( $variation, COUNT_RECURSIVE );

		if ( ! empty( $variation['styles'] ) ) {
			$weight += 1000;
		}

		if ( ! empty( $variation['settings']['typography']['fontFamilies'] ) ) {
			$weight += 500;
		}

		return $weight;
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
