<?php
/**
 * Block theme guard.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

defined( 'ABSPATH' ) || exit;

/**
 * FSE-only checks.
 */
final class Block_Theme_Guard {

	/**
	 * Whether the active theme is a block theme with theme.json.
	 */
	public static function is_supported(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Admin notice when the active theme is not FSE.
	 */
	public static function boot_admin_notice(): void {
		add_action(
			'admin_notices',
			static function (): void {
				if ( self::is_supported() || ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				if ( ! $screen || false === strpos( (string) $screen->id, 'forwp-style-switcher' ) ) {
					return;
				}

				echo '<div class="notice notice-warning"><p>';
				esc_html_e(
					'4WP Theme Style Switcher requires an active block theme (FSE) with theme.json style variations.',
					'4wp-style-switcher'
				);
				echo '</p></div>';
			}
		);
	}
}
