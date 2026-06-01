<?php
/**
 * Apply resolved style on the frontend.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Frontend;

use ForWP\StyleSwitcher\Block_Theme_Guard;
use ForWP\StyleSwitcher\Style_Registry;
use ForWP\StyleSwitcher\Style_Resolver;
use WP_Theme_JSON;
use WP_Theme_JSON_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Merges the resolved style variation into theme.json and adds body classes.
 */
final class Style_Applicator {

	/**
	 * Prepared WP_Theme_JSON objects keyed by origin:slug.
	 *
	 * @var array<string, WP_Theme_JSON>
	 */
	private static $prepared_theme_json = array();

	public static function boot(): void {
		add_filter( 'body_class', array( self::class, 'filter_body_class' ) );
		add_filter( 'wp_theme_json_data_theme', array( self::class, 'filter_theme_json_data' ) );
		add_filter( 'wp_theme_json_data_user', array( self::class, 'filter_user_json_data' ) );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function filter_body_class( array $classes ): array {
		if ( is_admin() || ! Block_Theme_Guard::is_supported() ) {
			return $classes;
		}

		$resolved = Style_Resolver::resolve();
		if ( '' !== $resolved['slug'] ) {
			$classes[] = 'forwp-ss-variation--' . $resolved['slug'];
		}

		if ( $resolved['locked'] ) {
			$classes[] = 'forwp-ss-style-locked';
		}

		return $classes;
	}

	/**
	 * Merge the active variation into theme data (typography, block styles, etc.).
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme data before merge into resolver cache.
	 */
	public static function filter_theme_json_data( WP_Theme_JSON_Data $theme_json ): WP_Theme_JSON_Data {
		return self::merge_active_variation( $theme_json, 'theme' );
	}

	/**
	 * Merge the active variation into user/custom data so palette presets override
	 * saved wp_global_styles from the Site Editor (custom origin wins over theme).
	 *
	 * @param WP_Theme_JSON_Data $user_json User data before merge into resolver cache.
	 */
	public static function filter_user_json_data( WP_Theme_JSON_Data $user_json ): WP_Theme_JSON_Data {
		return self::merge_active_variation( $user_json, 'custom', true );
	}

	/**
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON wrapper.
	 * @param string             $origin     theme|custom origin for preset paths.
	 * @param bool               $fire_hook  Whether to fire the extensibility action.
	 */
	private static function merge_active_variation( WP_Theme_JSON_Data $theme_json, string $origin, bool $fire_hook = false ): WP_Theme_JSON_Data {
		if ( is_admin() || ! Block_Theme_Guard::is_supported() ) {
			return $theme_json;
		}

		$resolved = Style_Resolver::resolve();
		if ( '' === $resolved['slug'] ) {
			return $theme_json;
		}

		$variation = Style_Registry::get_variation_raw( $resolved['slug'] );
		if ( null === $variation ) {
			return $theme_json;
		}

		$cache_key = $origin . ':' . $resolved['slug'];
		if ( ! isset( self::$prepared_theme_json[ $cache_key ] ) ) {
			self::$prepared_theme_json[ $cache_key ] = new WP_Theme_JSON(
				self::prepare_variation_for_merge( $variation ),
				$origin
			);
		}

		$theme = $theme_json->get_theme_json();
		$theme->merge( self::$prepared_theme_json[ $cache_key ] );

		if ( $fire_hook ) {
			/**
			 * Fires after the active style variation is merged into theme.json data.
			 *
			 * @param string               $slug     Variation slug.
			 * @param array<string, mixed> $resolved Resolver payload.
			 */
			do_action( 'forwp_style_switcher_enqueue_variation', $resolved['slug'], $resolved );
		}

		return new WP_Theme_JSON_Data( $theme->get_raw_data(), $origin );
	}

	/**
	 * Strip variation metadata before merging into theme.json.
	 *
	 * @param array<string, mixed> $variation Raw variation from the theme.
	 * @return array<string, mixed>
	 */
	private static function prepare_variation_for_merge( array $variation ): array {
		unset( $variation['title'], $variation['slug'], $variation['$schema'] );

		return $variation;
	}
}
