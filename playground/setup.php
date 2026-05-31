<?php
/**
 * WordPress Playground demo setup — plugin settings + menu Light/Dark block.
 *
 * @package ForWP\StyleSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin directory (Playground may use targetFolderName or a generated folder name).
 */
function forwp_ss_playground_plugin_dir(): string {
	static $dir = null;

	if ( null !== $dir ) {
		return $dir;
	}

	$dir = '';
	foreach ( glob( WP_PLUGIN_DIR . '/*/4wp-style-switcher.php' ) ?: array() as $bootstrap ) {
		$dir = dirname( $bootstrap );
		break;
	}

	if ( '' === $dir ) {
		$dir = WP_PLUGIN_DIR . '/4wp-style-switcher';
	}

	return $dir;
}

/**
 * Preconfigure three style variations and inject the menu toggle block.
 */
function forwp_ss_playground_setup(): void {
	forwp_ss_playground_save_settings();
	forwp_ss_playground_inject_menu_toggle();
}

/**
 * Morning (light), Afternoon, Midnight (dark) — easy contrast demo on Twenty Twenty-Five.
 */
function forwp_ss_playground_save_settings(): void {
	update_option(
		'forwp_ss_settings',
		array(
			'visitor_switcher_enabled' => true,
			'default_variation'        => 'morning',
			'switcher_position'        => 'bottom-right',
			'allowed_variations'       => array( 'morning', 'afternoon', 'midnight' ),
			'light_dark_mode_enabled'  => true,
			'light_variation'          => 'morning',
			'dark_variation'           => 'midnight',
			'visitor_storage_days'     => 365,
			'ab_testing'               => array(
				'enabled'         => false,
				'variation_a'     => '',
				'variation_b'     => '',
				'traffic_split_a' => 50,
			),
			'user_preferences'         => array(
				'enabled' => false,
			),
		),
		false
	);

	if ( class_exists( '\ForWP\StyleSwitcher\Ab_Testing\Ab_Stats_Table' ) ) {
		\ForWP\StyleSwitcher\Ab_Testing\Ab_Stats_Table::maybe_install();
	}
}

/**
 * Append the Light/Dark block to the first Navigation menu entity.
 */
function forwp_ss_playground_inject_menu_toggle(): void {
	$toggle = "\n<!-- wp:forwp-style-switcher/light-dark-toggle /-->\n";

	$posts = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'posts_per_page' => 10,
			'post_status'    => array( 'publish', 'draft' ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $posts as $post ) {
		if ( false !== strpos( $post->post_content, 'forwp-style-switcher/light-dark-toggle' ) ) {
			return;
		}

		$content = trim( $post->post_content );
		if ( '' === $content ) {
			$home_id = (int) get_option( 'page_on_front' );
			if ( $home_id > 0 ) {
				$content = '<!-- wp:navigation-link {"label":"Home","type":"page","id":' . $home_id . ',"url":"/","kind":"post-type"} /-->';
			} else {
				$content = '<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->';
			}
		}

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $content . $toggle,
			)
		);

		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'Playground navigation',
			'post_content' => '<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->' . $toggle,
		)
	);
}
