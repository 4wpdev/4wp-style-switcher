<?php
/**
 * Post meta keys for per-page style.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher;

defined( 'ABSPATH' ) || exit;

/**
 * Meta key constants.
 */
final class Meta_Keys {

	public const PAGE_STYLE_SLUG = '_forwp_ss_page_style';

	public const PAGE_STYLE_LOCKED = '_forwp_ss_page_style_locked';

	/**
	 * Post types that support page style meta.
	 *
	 * @return string[]
	 */
	public static function post_types(): array {
		/**
		 * Filter post types that expose the page style panel.
		 *
		 * @param string[] $post_types Post type slugs.
		 */
		return apply_filters(
			'forwp_style_switcher_post_types',
			array( 'page', 'post' )
		);
	}
}
