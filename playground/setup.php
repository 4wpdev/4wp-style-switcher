<?php
/**
 * WordPress Playground demo — Style Switcher showcase site.
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
 * Run full Playground setup.
 */
function forwp_ss_playground_setup(): void {
	$slugs = forwp_ss_playground_resolve_demo_slugs();

	forwp_ss_playground_save_settings( $slugs );
	forwp_ss_playground_configure_site();

	$pages = forwp_ss_playground_create_pages( $slugs );
	$nav_id = forwp_ss_playground_create_navigation( $pages );

	forwp_ss_playground_apply_template_parts( $nav_id );
}

/**
 * @return array{
 *   allowed: string[],
 *   default: string,
 *   light: string,
 *   dark: string,
 *   medium: string,
 *   exotic: string  Non-standard variation slug from the active theme (e.g. evening).
 * }
 */
function forwp_ss_playground_resolve_demo_slugs(): array {
	$fallback = array(
		'allowed' => array( 'morning', 'afternoon', 'midnight', 'evening' ),
		'default' => 'morning',
		'light'   => 'morning',
		'dark'    => 'midnight',
		'medium'  => 'afternoon',
		'exotic'  => 'evening',
	);

	if ( ! class_exists( '\ForWP\StyleSwitcher\Style_Registry' ) ) {
		return $fallback;
	}

	$variations = \ForWP\StyleSwitcher\Style_Registry::get_theme_variations();
	if ( empty( $variations ) ) {
		return $fallback;
	}

	$color_variations = array_values(
		array_filter(
			$variations,
			static function ( array $variation ): bool {
				$slug = $variation['slug'];

				return false === strpos( $slug, 'typography' )
					&& false === strpos( $slug, 'section' )
					&& false === strpos( $slug, 'block' );
			}
		)
	);

	if ( empty( $color_variations ) ) {
		$color_variations = $variations;
	}

	$pick = static function ( array $keywords ) use ( $color_variations ): string {
		foreach ( $keywords as $keyword ) {
			foreach ( $color_variations as $variation ) {
				$slug  = $variation['slug'];
				$title = isset( $variation['title'] ) ? (string) $variation['title'] : $slug;

				if ( false !== stripos( $slug, $keyword ) || false !== stripos( $title, $keyword ) ) {
					return $slug;
				}
			}
		}

		return '';
	};

	$light  = $pick( array( 'morning', 'sunrise', 'day' ) );
	$medium = $pick( array( 'afternoon', 'noon' ) );
	$dark   = $pick( array( 'midnight', 'night' ) );
	$exotic = $pick( array( 'evening', 'twilight', 'dusk', 'sunset' ) );

	$core = array_values(
		array_unique(
			array_filter(
				array( $light, $medium, $dark ),
				static function ( string $slug ): bool {
					return '' !== $slug;
				}
			)
		)
	);

	if ( '' === $exotic ) {
		foreach ( $color_variations as $variation ) {
			if ( ! in_array( $variation['slug'], $core, true ) ) {
				$exotic = $variation['slug'];
				break;
			}
		}
	}

	if ( count( $core ) < 2 ) {
		$core = array_slice( array_column( $color_variations, 'slug' ), 0, 3 );
	}

	if ( empty( $core ) ) {
		return $fallback;
	}

	if ( '' === $light ) {
		$light = $core[0];
	}
	if ( '' === $dark ) {
		$dark = $core[ count( $core ) - 1 ];
	}
	if ( '' === $medium ) {
		$medium = $core[1] ?? $light;
	}
	if ( '' === $exotic ) {
		$exotic = $dark;
	}

	$allowed = array_values( array_unique( array_merge( $core, array( $exotic ) ) ) );

	return array(
		'allowed' => $allowed,
		'default' => $light,
		'light'   => $light,
		'dark'    => $dark,
		'medium'  => $medium,
		'exotic'  => $exotic,
	);
}

/**
 * @param array<string, mixed> $slugs Resolved variation slugs.
 */
function forwp_ss_playground_save_settings( array $slugs ): void {
	update_option(
		'forwp_ss_settings',
		array(
			'visitor_switcher_enabled' => true,
			'default_variation'        => $slugs['default'],
			'switcher_position'        => 'bottom-right',
			'allowed_variations'       => $slugs['allowed'],
			'light_dark_mode_enabled'  => true,
			'light_variation'          => $slugs['light'],
			'dark_variation'           => $slugs['dark'],
			'visitor_storage_days'     => 365,
			'ab_testing'               => array(
				'enabled'         => true,
				'variation_a'     => $slugs['light'],
				'variation_b'     => $slugs['dark'],
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

function forwp_ss_playground_configure_site(): void {
	update_option( 'blogname', 'Style Switcher', false );
	update_option(
		'blogdescription',
		'Demo: switch block theme style variations — per-page styles, Light/Dark, A/B testing.',
		false
	);
}

function forwp_ss_playground_variation_title( string $slug ): string {
	if ( class_exists( '\ForWP\StyleSwitcher\Style_Registry' ) ) {
		foreach ( \ForWP\StyleSwitcher\Style_Registry::get_theme_variations() as $variation ) {
			if ( $variation['slug'] === $slug ) {
				return $variation['title'];
			}
		}
	}

	return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
}

/**
 * @param array<string, mixed> $slugs Variation slugs.
 * @return array<string, int> Page keys → post IDs.
 */
function forwp_ss_playground_create_pages( array $slugs ): array {
	$alternate_title = forwp_ss_playground_variation_title( $slugs['exotic'] );

	$definitions = array(
		'about' => array(
			'title'   => 'About Plugin',
			'slug'    => 'about-plugin',
			'style'   => $slugs['light'],
			'locked'  => false,
			'content' => forwp_ss_playground_about_content( $slugs, $alternate_title ),
		),
		'morning' => array(
			'title'   => 'Morning',
			'slug'    => 'morning',
			'style'   => $slugs['light'],
			'locked'  => false,
			'content' => forwp_ss_playground_page_content(
				'Morning',
				'Page with the <strong>Morning</strong> style (light variation). Visitors can change the style via the bottom-right switcher or Light/Dark in the menu.'
			),
		),
		'afternoon' => array(
			'title'   => 'Afternoon',
			'slug'    => 'afternoon',
			'style'   => $slugs['medium'],
			'locked'  => false,
			'content' => forwp_ss_playground_page_content(
				'Afternoon',
				'Page with the <strong>Afternoon</strong> style — a mid-tone palette. Style is set for this page but not locked.'
			),
		),
		'night' => array(
			'title'   => 'Night',
			'slug'    => 'night',
			'style'   => $slugs['dark'],
			'locked'  => true,
			'content' => forwp_ss_playground_page_content(
				'Night',
				'Dark variation, <strong>locked</strong> for visitors. The switcher on this page will not change the style.'
			),
		),
		'alternate' => array(
			'title'   => $alternate_title,
			'slug'    => $slugs['exotic'],
			'style'   => $slugs['exotic'],
			'locked'  => true,
			'content' => forwp_ss_playground_page_content(
				$alternate_title,
				sprintf(
					'This page uses the theme\'s <strong>%1$s</strong> style variation — an alternate preset beyond typical light / mid / dark. The plugin does not edit <code>theme.json</code>; it applies variations the active block theme already provides. Style is <strong>locked</strong> on this page.',
					esc_html( $alternate_title )
				)
			),
		),
	);

	$ids = array();

	foreach ( $definitions as $key => $page ) {
		$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			$id = (int) $existing->ID;
			wp_update_post(
				array(
					'ID'           => $id,
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
				)
			);
		} else {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => $page['slug'],
					'post_content' => $page['content'],
				),
				true
			);
		}

		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}

		update_post_meta( $id, '_forwp_ss_page_style', sanitize_title( $page['style'] ) );
		update_post_meta( $id, '_forwp_ss_page_style_locked', $page['locked'] ? '1' : '0' );

		$ids[ $key ] = (int) $id;
	}

	if ( ! empty( $ids['about'] ) ) {
		update_option( 'show_on_front', 'page', false );
		update_option( 'page_on_front', $ids['about'], false );
	}

	return $ids;
}

function forwp_ss_playground_about_content( array $slugs, string $alternate_title ): string {
	$alternate_slug = sanitize_title( $slugs['exotic'] );

	return '<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">4WP Style Switcher</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">Plugin for <strong>block themes (FSE)</strong>: switch between light, dark, and other <strong>style variations</strong> that ship with the active theme — no extra CSS files, and no editing <code>theme.json</code> from the plugin.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">What it solves</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Light and dark theme for visitors (cookie + switcher)</li><li>Light/Dark toggle in the Navigation menu</li><li>A different style per page</li><li>Lock style on landing / promo pages</li><li>A/B test two variations for new visitors</li></ul>
<!-- /wp:list -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Why style variations?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>In FSE, the <strong>theme author</strong> defines the design system and optional presets in <code>theme.json</code> (often as <code>/styles/*.json</code> files). This plugin <strong>uses</strong> those built-in variations — it does not create or maintain them. You help the client choose among real presets (Morning / Afternoon / Night / ' . esc_html( $alternate_title ) . '), not one-off custom CSS.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>This demo: front page is light; see <a href="/morning/">Morning</a>, <a href="/afternoon/">Afternoon</a>, <a href="/night/">Night</a>, and <a href="/' . esc_attr( $alternate_slug ) . '/">' . esc_html( $alternate_title ) . '</a> (an alternate theme preset). <strong>Night</strong> and <strong>' . esc_html( $alternate_title ) . '</strong> have visitor switching locked.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Try it</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Bottom-right switcher — all allowed variations from the theme</li><li>Sun/moon icon in the header — Light/Dark</li><li>A/B: new visitors without a cookie get light or dark at random</li></ul>
<!-- /wp:list -->

</div>
<!-- /wp:group -->';
}

function forwp_ss_playground_page_content( string $title, string $intro_html ): string {
	return '<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">' . esc_html( $title ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">' . wp_kses_post( $intro_html ) . '</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->';
}

/**
 * @param array<string, int> $pages Page key → post ID.
 */
function forwp_ss_playground_create_navigation( array $pages ): int {
	$links = array( 'about', 'morning', 'afternoon', 'night', 'alternate' );

	$content = '';
	foreach ( $links as $key ) {
		if ( empty( $pages[ $key ] ) ) {
			continue;
		}
		$content .= forwp_ss_playground_nav_link_block( $pages[ $key ], get_the_title( $pages[ $key ] ) ) . "\n";
	}
	$content .= "\n<!-- wp:forwp-style-switcher/light-dark-toggle /-->\n";

	$existing = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'name'           => 'style-switcher-menu',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);

	if ( ! empty( $existing ) ) {
		wp_update_post(
			array(
				'ID'           => $existing[0]->ID,
				'post_content' => $content,
			)
		);
		return (int) $existing[0]->ID;
	}

	return (int) wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'Style Switcher Menu',
			'post_name'    => 'style-switcher-menu',
			'post_content' => $content,
		)
	);
}

function forwp_ss_playground_nav_link_block( int $page_id, string $label ): string {
	$attrs = wp_json_encode(
		array(
			'label' => $label,
			'type'  => 'page',
			'id'    => $page_id,
			'url'   => get_permalink( $page_id ),
			'kind'  => 'post-type',
		)
	);

	return '<!-- wp:navigation-link ' . $attrs . ' /-->';
}

/**
 * Wire header + footer template parts to the shared navigation.
 */
function forwp_ss_playground_apply_template_parts( int $nav_id ): void {
	if ( $nav_id < 1 ) {
		return;
	}

	$nav_ref = wp_json_encode( array( 'ref' => $nav_id ) );

	$header = '<!-- wp:group {"align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull">
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
<!-- wp:site-title {"level":0} /-->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group">
<!-- wp:navigation ' . $nav_ref . ' /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->';

	$footer = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:site-title {"level":2,"fontSize":"medium"} /-->
<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} ' . $nav_ref . ' /-->
</div>
<!-- /wp:group -->
<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Style Switcher demo · 4WP Style Switcher plugin · Twenty Twenty-Five</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->';

	foreach ( array( 'header' => $header, 'footer' => $footer ) as $slug => $content ) {
		forwp_ss_playground_update_template_part( $slug, $content );
	}
}

function forwp_ss_playground_update_template_part( string $slug, string $content ): void {
	$parts = get_posts(
		array(
			'post_type'      => 'wp_template_part',
			'name'           => $slug,
			'posts_per_page' => 5,
			'post_status'    => array( 'publish', 'draft' ),
		)
	);

	foreach ( $parts as $part ) {
		wp_update_post(
			array(
				'ID'           => $part->ID,
				'post_content' => $content,
			)
		);
	}
}
