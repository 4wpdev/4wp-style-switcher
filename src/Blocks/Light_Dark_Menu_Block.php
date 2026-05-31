<?php
/**
 * Light / Dark toggle block for navigation menus.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Blocks;

use ForWP\StyleSwitcher\Frontend\Visitor_Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the menu Light / Dark block from block.json metadata.
 */
final class Light_Dark_Menu_Block {

	public const BLOCK_NAME = 'forwp-style-switcher/light-dark-toggle';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'render_block_' . self::BLOCK_NAME, array( self::class, 'enqueue_block_assets' ), 10, 2 );
		add_filter( 'script_loader_tag', array( self::class, 'remove_view_script_defer' ), 10, 3 );
		add_filter( 'block_core_navigation_listable_blocks', array( self::class, 'add_to_navigation_list' ) );
		add_filter( 'block_type_metadata_settings', array( self::class, 'allow_in_navigation' ), 10, 2 );
	}

	public static function register(): void {
		Visitor_Storage::register_assets();

		register_block_type( FORWP_STYLE_SWITCHER_PATH . 'blocks/light-dark-toggle' );
	}

	/**
	 * Ensure frontend scripts load when the block is rendered.
	 *
	 * @param string               $content Block HTML.
	 * @param array<string, mixed> $block   Parsed block.
	 */
	public static function enqueue_block_assets( string $content, array $block ): string {
		if ( is_admin() || empty( $content ) ) {
			return $content;
		}

		Visitor_Storage::enqueue_assets();

		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );
		if ( $block_type && ! empty( $block_type->view_script_handles ) ) {
			foreach ( $block_type->view_script_handles as $handle ) {
				wp_enqueue_script( $handle );
			}
		}

		return $content;
	}

	/**
	 * Run the toggle handler synchronously; defer breaks ordering inside Navigation.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script src.
	 */
	public static function remove_view_script_defer( string $tag, string $handle, string $src ): string {
		if ( 'forwp-style-switcher-light-dark-toggle-view-script' !== $handle ) {
			return $tag;
		}

		return str_replace(
			array( " data-wp-strategy='defer'", ' data-wp-strategy="defer"', ' defer', " defer='defer'" ),
			'',
			$tag
		);
	}

	/**
	 * @param string[] $blocks Blocks that need a list item wrapper in navigation.
	 * @return string[]
	 */
	public static function add_to_navigation_list( array $blocks ): array {
		if ( ! in_array( self::BLOCK_NAME, $blocks, true ) ) {
			$blocks[] = self::BLOCK_NAME;
		}

		return $blocks;
	}

	/**
	 * @param array<string, mixed> $settings Block type settings.
	 * @param array<string, mixed> $metadata Block metadata.
	 * @return array<string, mixed>
	 */
	public static function allow_in_navigation( array $settings, array $metadata ): array {
		if ( 'core/navigation' !== ( $metadata['name'] ?? '' ) ) {
			return $settings;
		}

		if ( ! isset( $settings['allowed_blocks'] ) || ! is_array( $settings['allowed_blocks'] ) ) {
			$settings['allowed_blocks'] = array();
		}

		if ( ! in_array( self::BLOCK_NAME, $settings['allowed_blocks'], true ) ) {
			$settings['allowed_blocks'][] = self::BLOCK_NAME;
		}

		return $settings;
	}
}
