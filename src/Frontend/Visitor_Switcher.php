<?php
/**
 * Visitor-facing style switcher UI.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Frontend;

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Block_Theme_Guard;
use ForWP\StyleSwitcher\Frontend\Visitor_Storage;
use ForWP\StyleSwitcher\Style_Registry;
use ForWP\StyleSwitcher\Style_Resolver;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the frontend toggle and passes variation choices to JS.
 */
final class Visitor_Switcher {

	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( self::class, 'render_switcher' ), 5 );
	}

	public static function enqueue_assets(): void {
		if ( is_admin() || ! self::should_render() ) {
			return;
		}

		wp_enqueue_style(
			'forwp-ss-frontend',
			FORWP_STYLE_SWITCHER_URL . 'assets/frontend-switcher.css',
			array(),
			FORWP_STYLE_SWITCHER_VERSION
		);

		wp_enqueue_script(
			'forwp-ss-frontend',
			FORWP_STYLE_SWITCHER_URL . 'assets/frontend-switcher.js',
			array( 'forwp-ss-visitor-storage' ),
			FORWP_STYLE_SWITCHER_VERSION,
			true
		);

		Visitor_Storage::enqueue_assets();

		wp_localize_script(
			'forwp-ss-frontend',
			'forwpStyleSwitcher',
			array(
				'variations' => Style_Registry::get_variations(),
				'active'     => Style_Resolver::resolve()['slug'],
				'storage'    => Visitor_Storage::get_client_config(),
				'position'   => Settings::instance()->get_switcher_position(),
				'uiMode'     => 'select',
				'strings'    => array(
					'label' => __( 'Site style', '4wp-style-switcher' ),
				),
			)
		);
	}

	public static function render_switcher(): void {
		if ( ! self::should_render() ) {
			return;
		}

		$position = Settings::instance()->get_switcher_position();
		?>
		<div
			id="forwp-ss-switcher-root"
			class="forwp-ss-switcher forwp-ss-switcher--<?php echo esc_attr( $position ); ?>"
			data-forwp-ss-switcher
			hidden
		></div>
		<?php
	}

	private static function should_render(): bool {
		if ( ! Block_Theme_Guard::is_supported() ) {
			return false;
		}

		$resolved = Style_Resolver::resolve();

		return $resolved['show_switcher'];
	}
}
