<?php
/**
 * Frontend A/B visitor assignment.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Ab_Testing;

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Block_Theme_Guard;
use ForWP\StyleSwitcher\Meta_Keys;
use ForWP\StyleSwitcher\Style_Resolver;

defined( 'ABSPATH' ) || exit;

/**
 * Assigns new visitors to cohort A or B and persists the style cookie.
 */
final class Ab_Assignment {

	public static function boot(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_assign' ), 2 );
	}

	public static function maybe_assign(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! Block_Theme_Guard::is_supported() || ! Ab_Testing::is_ready() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id > 0 && (bool) get_post_meta( $post_id, Meta_Keys::PAGE_STYLE_LOCKED, true ) ) {
			return;
		}

		$existing = Style_Resolver::read_visitor_preference();
		if ( '' !== $existing && Style_Resolver::is_valid_slug( $existing ) ) {
			return;
		}

		$result = Ab_Testing::assign_cohort();
		if ( null === $result ) {
			return;
		}

		self::persist_assignment( $result );

		/**
		 * Fires after a visitor is assigned to an A/B cohort.
		 *
		 * @param array{cohort: string, slug: string} $result Assignment.
		 * @param array<string, mixed>               $config A/B settings.
		 */
		do_action( 'forwp_style_switcher_ab_assigned', $result, Ab_Testing::get_config() );
	}

	/**
	 * @param array{cohort: string, slug: string} $result Assignment.
	 */
	private static function persist_assignment( array $result ): void {
		$slug = sanitize_title( (string) $result['slug'] );
		if ( '' === $slug ) {
			return;
		}

		$days = Settings::instance()->get_visitor_storage_days();

		setcookie(
			Style_Resolver::VISITOR_COOKIE,
			$slug,
			time() + ( $days * DAY_IN_SECONDS ),
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			false
		);

		$_COOKIE[ Style_Resolver::VISITOR_COOKIE ] = $slug;

		Ab_Stats_Table::record_assignment( (string) $result['cohort'] );
	}
}
