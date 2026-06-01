<?php
/**
 * Plugin Name:       4WP Style Switcher
 * Plugin URI:        https://github.com/4wpdev/4wp-style-switcher
 * Description:       Apply theme.json style variations per page; visitor style switcher and Light/Dark menu toggle for FSE block themes.
 * Version:           0.2.4
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            4wpdev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       4wp-style-switcher
 *
 * @package ForWP\StyleSwitcher
 */

defined( 'ABSPATH' ) || exit;

define( 'FORWP_STYLE_SWITCHER_VERSION', '0.2.4' );

define(
	'FORWP_STYLE_SWITCHER_PLAYGROUND_URL',
	'https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/4wpdev/4wp-style-switcher/v0.2.4/.wordpress-org/assets/blueprints/blueprint.json'
);
define( 'FORWP_STYLE_SWITCHER_FILE', __FILE__ );
define( 'FORWP_STYLE_SWITCHER_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORWP_STYLE_SWITCHER_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( FORWP_STYLE_SWITCHER_PATH . 'vendor/autoload.php' ) ) {
	require_once FORWP_STYLE_SWITCHER_PATH . 'vendor/autoload.php';
} else {
	require_once FORWP_STYLE_SWITCHER_PATH . 'src/Autoload.php';
	ForWP\StyleSwitcher\Autoload::register();
}

ForWP\StyleSwitcher\Plugin::instance()->boot();

register_activation_hook(
	FORWP_STYLE_SWITCHER_FILE,
	static function (): void {
		if ( class_exists( '\ForWP\StyleSwitcher\Ab_Testing\Ab_Stats_Table' ) ) {
			\ForWP\StyleSwitcher\Ab_Testing\Ab_Stats_Table::install();
		}
	}
);
