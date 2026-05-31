<?php
/**
 * Plugin bootstrap.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

use ForWP\StyleSwitcher\Admin\Admin_Menu;
use ForWP\StyleSwitcher\Ab_Testing\Ab_Testing;
use ForWP\StyleSwitcher\Analytics\Analytics;
use ForWP\StyleSwitcher\Block_Theme_Guard;
use ForWP\StyleSwitcher\Blocks\Light_Dark_Menu_Block;
use ForWP\StyleSwitcher\Editor\Page_Style_Meta;
use ForWP\StyleSwitcher\Frontend\Style_Applicator;
use ForWP\StyleSwitcher\Frontend\Visitor_Storage;
use ForWP\StyleSwitcher\Frontend\Visitor_Switcher;
use ForWP\StyleSwitcher\Rest\Rest_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin singleton.
 */
final class Plugin {

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

	/**
	 * Boot hooks.
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		Page_Style_Meta::boot();
		Block_Theme_Guard::boot_admin_notice();
		Light_Dark_Menu_Block::boot();
		Ab_Testing::boot();
		Analytics::boot();
		Admin_Menu::instance()->boot();
		Visitor_Storage::boot();
		Style_Applicator::boot();
		Visitor_Switcher::boot();
		Rest_Settings::register();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'4wp-style-switcher',
			false,
			dirname( plugin_basename( FORWP_STYLE_SWITCHER_FILE ) ) . '/languages'
		);
	}
}
