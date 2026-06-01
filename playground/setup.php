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
	forwp_ss_playground_remove_retired_pages();
	$nav_id = forwp_ss_playground_create_navigation( $pages );

	forwp_ss_playground_apply_template_parts( $nav_id );
}

/**
 * @return array{
 *   allowed: string[],
 *   default: string,
 *   light: string,
 *   dark: string,
 *   morning: string,
 *   afternoon: string,
 *   evening: string
 * }
 */
function forwp_ss_playground_resolve_demo_slugs(): array {
	$fallback = array(
		'allowed'   => array( 'morning', 'afternoon', 'evening', 'midnight' ),
		'default'   => 'morning',
		'light'     => 'morning',
		'dark'      => 'midnight',
		'morning'   => 'morning',
		'afternoon' => 'afternoon',
		'evening'   => 'evening',
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

	$find = static function ( string $preferred_slug ) use ( $color_variations ): string {
		$preferred_slug = sanitize_title( $preferred_slug );
		foreach ( $color_variations as $variation ) {
			if ( $variation['slug'] === $preferred_slug ) {
				return $preferred_slug;
			}
		}
		foreach ( $color_variations as $variation ) {
			if ( 0 === strcasecmp( (string) $variation['title'], $preferred_slug ) ) {
				return $variation['slug'];
			}
		}
		return '';
	};

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

	$morning   = $find( 'morning' ) ?: $pick( array( 'morning', 'sunrise' ) );
	$afternoon = $find( 'afternoon' ) ?: $pick( array( 'afternoon' ) );
	$evening   = $find( 'evening' ) ?: $pick( array( 'evening', 'twilight', 'dusk', 'sunset' ) );
	$dark      = $find( 'midnight' ) ?: $find( 'night' ) ?: $pick( array( 'midnight', 'night' ) );

	$core = array_values(
		array_unique(
			array_filter(
				array( $morning, $afternoon, $evening, $dark ),
				static function ( string $slug ): bool {
					return '' !== $slug;
				}
			)
		)
	);

	if ( count( $core ) < 2 ) {
		$core = array_slice( array_column( $color_variations, 'slug' ), 0, 4 );
	}

	if ( empty( $core ) ) {
		return $fallback;
	}

	if ( '' === $morning ) {
		$morning = $core[0];
	}
	if ( '' === $afternoon ) {
		$afternoon = $core[1] ?? $morning;
	}
	if ( '' === $evening ) {
		$evening = $core[2] ?? $afternoon;
	}
	if ( '' === $dark ) {
		$dark = $core[ count( $core ) - 1 ];
	}

	// Avoid demo pages sharing one slug (e.g. fuzzy keyword match).
	if ( $morning === $afternoon ) {
		$afternoon = $find( 'afternoon' ) ?: $pick( array( 'noon' ) );
	}
	if ( $afternoon === $morning || '' === $afternoon ) {
		$afternoon = $pick( array( 'noon' ) );
	}

	$allowed = array_values( array_unique( array_filter( array( $morning, $afternoon, $evening, $dark ) ) ) );

	return array(
		'allowed'   => $allowed,
		'default'   => $morning,
		'light'     => $morning,
		'dark'      => $dark,
		'morning'   => $morning,
		'afternoon' => $afternoon,
		'evening'   => $evening,
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
	$evening_title = forwp_ss_playground_variation_title( $slugs['evening'] );
	$dark_title    = forwp_ss_playground_variation_title( $slugs['dark'] );

	$definitions = array(
		'about' => array(
			'nav_label' => 'About Plugin',
			'title'   => 'About Plugin',
			'slug'    => 'about-plugin',
			'style'   => $slugs['morning'],
			'locked'  => false,
			'content' => forwp_ss_playground_about_content( $slugs, $evening_title, $dark_title ),
		),
		'morning' => array(
			'nav_label' => 'Morning',
			'title'   => forwp_ss_playground_variation_title( $slugs['morning'] ),
			'slug'    => 'morning',
			'style'   => $slugs['morning'],
			'locked'  => false,
			'content' => forwp_ss_playground_page_content(
				$slugs['morning'],
				'Visitors can switch styles via the bottom-right switcher or Light/Dark in the menu.'
			),
		),
		'afternoon' => array(
			'nav_label' => 'Afternoon',
			'title'   => forwp_ss_playground_variation_title( $slugs['afternoon'] ),
			'slug'    => 'afternoon',
			'style'   => $slugs['afternoon'],
			'locked'  => true,
			'content' => forwp_ss_playground_page_content(
				$slugs['afternoon'],
				'Switching is <strong>locked</strong> on this page — visitors keep the assigned per-page style.'
			),
		),
		'evening' => array(
			'nav_label' => 'Evening',
			'title'   => forwp_ss_playground_variation_title( $slugs['evening'] ),
			'slug'    => 'evening',
			'style'   => $slugs['evening'],
			'locked'  => false,
			'content' => forwp_ss_playground_page_content(
				$slugs['evening'],
				'Visitors can switch styles via the bottom-right switcher or Light/Dark in the menu.'
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

/**
 * Remove demo pages dropped from the Playground blueprint.
 */
function forwp_ss_playground_remove_retired_pages(): void {
	foreach ( array( 'night' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			wp_trash_post( $page->ID );
		}
	}
}

function forwp_ss_playground_about_content( array $slugs, string $evening_title, string $dark_title ): string {
	return '<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">4WP Style Switcher</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">Plugin for <strong>block themes (FSE)</strong>: each page can use its own style variation from the active theme — Morning, Afternoon, Evening, and more.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">What it solves</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Light and dark theme for visitors (cookie + switcher)</li><li>Light/Dark toggle in the Navigation menu</li><li>A dedicated style variation per page</li><li>Lock switching on selected pages (demo: Afternoon)</li><li>A/B test two variations for new visitors</li></ul>
<!-- /wp:list -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Why style variations?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The theme author ships presets in <code>theme.json</code> (e.g. Morning, Afternoon, ' . esc_html( $evening_title ) . '). This plugin applies them per page — it does not edit theme files.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>This demo assigns one variation per page: <a href="/morning/">Morning</a>, <a href="/afternoon/">Afternoon</a> (locked), and <a href="/evening/">Evening</a> (' . esc_html( $evening_title ) . ', switching allowed).</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Try it</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><li>Bottom-right switcher — allowed theme variations</li><li>Sun/moon icon in the header — Light/Dark (Morning vs ' . esc_html( $dark_title ) . ')</li><li>Visit Afternoon — switching is disabled; Evening — switching works</li></ul>
<!-- /wp:list -->

</div>
<!-- /wp:group -->';
}

function forwp_ss_playground_page_content( string $variation_slug, string $intro_html ): string {
	return '<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><code>' . esc_html( $variation_slug ) . '</code> · theme.json style variation</p>
<!-- /wp:paragraph -->

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
	$links = array( 'about', 'morning', 'afternoon', 'evening' );

	$nav_labels = array(
		'about'     => 'About Plugin',
		'morning'   => 'Morning',
		'afternoon' => 'Afternoon',
		'evening'   => 'Evening',
	);

	$content = '';
	foreach ( $links as $key ) {
		if ( empty( $pages[ $key ] ) ) {
			continue;
		}
		$label = $nav_labels[ $key ] ?? get_the_title( $pages[ $key ] );
		$content .= forwp_ss_playground_nav_link_block( $pages[ $key ], $label ) . "\n";
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
