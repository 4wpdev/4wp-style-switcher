<?php
/**
 * Admin menu and assets.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Admin;

use ForWP\StyleSwitcher\Ab_Testing\Ab_Testing;
use ForWP\StyleSwitcher\Style_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registers settings screen.
 */
final class Admin_Menu {

	private const MENU_SLUG = 'forwp-style-switcher';

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'migrate_settings' ) );
	}

	public function migrate_settings(): void {
		Settings::instance()->migrate_allowed_variations();
	}

	public function register_menu(): void {
		add_options_page(
			__( '4WP Style Switcher', '4wp-style-switcher' ),
			__( '4WP Style Switcher', '4wp-style-switcher' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_settings' )
		);
	}

	/**
	 * @param string $hook_suffix Admin screen hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'forwp-ss-admin',
			FORWP_STYLE_SWITCHER_URL . 'assets/admin.css',
			array( 'wp-components' ),
			FORWP_STYLE_SWITCHER_VERSION
		);

		wp_enqueue_script(
			'forwp-ss-admin',
			FORWP_STYLE_SWITCHER_URL . 'assets/admin.js',
			array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
			FORWP_STYLE_SWITCHER_VERSION,
			true
		);

		wp_localize_script(
			'forwp-ss-admin',
			'forwpStyleSwitcherAdmin',
			array(
				'restUrl'          => rest_url( 'forwp-style-switcher/v1/' ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'bootstrap'        => array(
					'settings'         => Settings::instance()->all(),
					'theme_variations' => Style_Registry::get_theme_variations(),
					'ab_testing'       => Ab_Testing::get_config(),
				),
				'strings'          => array(
					'saved'         => __( 'Settings saved.', '4wp-style-switcher' ),
					'error'         => __( 'Could not save settings.', '4wp-style-switcher' ),
					'saving'        => __( 'Saving…', '4wp-style-switcher' ),
					'noVariations'  => __( 'No style variations found in the active theme.', '4wp-style-switcher' ),
				),
			)
		);
	}

	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require FORWP_STYLE_SWITCHER_PATH . 'views/settings-page.php';
	}
}
