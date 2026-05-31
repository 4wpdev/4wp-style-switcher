<?php
/**
 * Per-page style post meta (editor + REST).
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Editor;

use ForWP\StyleSwitcher\Meta_Keys;

defined( 'ABSPATH' ) || exit;

/**
 * Registers page style meta for supported post types.
 */
final class Page_Style_Meta {

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );
	}

	public static function register_meta(): void {
		foreach ( Meta_Keys::post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				Meta_Keys::PAGE_STYLE_SLUG,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => static fn () => current_user_can( 'edit_posts' ),
					'sanitize_callback' => 'sanitize_title',
				)
			);

			register_post_meta(
				$post_type,
				Meta_Keys::PAGE_STYLE_LOCKED,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => static fn () => current_user_can( 'edit_posts' ),
					'sanitize_callback' => static fn ( $value ) => (bool) $value,
				)
			);
		}
	}

	public static function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'forwp-ss-editor',
			FORWP_STYLE_SWITCHER_URL . 'assets/editor.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-i18n', 'wp-element' ),
			FORWP_STYLE_SWITCHER_VERSION,
			true
		);

		wp_enqueue_style(
			'forwp-ss-editor',
			FORWP_STYLE_SWITCHER_URL . 'assets/admin.css',
			array( 'wp-components' ),
			FORWP_STYLE_SWITCHER_VERSION
		);
	}
}
