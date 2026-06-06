<?php
/**
 * A/B testing configuration and cohort selection.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Ab_Testing;

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Style_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Reads A/B settings, picks cohorts, and exposes stats.
 */
final class Ab_Testing {

	public static function boot(): void {
		add_action( 'init', array( Ab_Stats_Table::class, 'maybe_install' ), 1 );
		Ab_Assignment::boot();
	}

	/**
	 * @return array{
	 *   enabled: bool,
	 *   variation_a: string,
	 *   variation_b: string,
	 *   traffic_split_a: int,
	 *   traffic_split_b: int,
	 *   status: string
	 * }
	 */
	public static function get_config(): array {
		$raw   = Settings::instance()->get_ab_testing_settings();
		$split = (int) ( $raw['traffic_split_a'] ?? 50 );

		return array(
			'enabled'         => ! empty( $raw['enabled'] ),
			'variation_a'     => sanitize_title( (string) ( $raw['variation_a'] ?? '' ) ),
			'variation_b'     => sanitize_title( (string) ( $raw['variation_b'] ?? '' ) ),
			'traffic_split_a' => $split,
			'traffic_split_b' => 100 - $split,
			'status'          => self::is_ready() ? 'active' : 'inactive',
		);
	}

	public static function is_ready(): bool {
		$raw = Settings::instance()->get_ab_testing_settings();

		if ( empty( $raw['enabled'] ) ) {
			return false;
		}

		$variation_a = sanitize_title( (string) ( $raw['variation_a'] ?? '' ) );
		$variation_b = sanitize_title( (string) ( $raw['variation_b'] ?? '' ) );

		if ( '' === $variation_a || '' === $variation_b ) {
			return false;
		}

		if ( $variation_a === $variation_b ) {
			return false;
		}

		$allowed = Settings::instance()->get_allowed_variation_slugs();

		return in_array( $variation_a, $allowed, true )
			&& in_array( $variation_b, $allowed, true )
			&& null !== Style_Registry::get_variation( $variation_a )
			&& null !== Style_Registry::get_variation( $variation_b );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_stats_summary(): array {
		return Ab_Stats_Table::get_summary();
	}

	/**
	 * Pick cohort A or B for a new visitor.
	 *
	 * @return array{cohort: string, slug: string}|null
	 */
	public static function assign_cohort(): ?array {
		if ( ! self::is_ready() ) {
			return null;
		}

		/**
		 * Filter whether A/B assignment runs on the current request.
		 *
		 * @param bool $run Default true when the test is ready.
		 */
		$run = apply_filters( 'forwp_style_switcher_ab_assignment_enabled', true );

		if ( ! $run ) {
			return null;
		}

		$config = self::get_config();
		$cohort = self::pick_cohort( $config['traffic_split_a'], Ab_Stats_Table::get_totals() );
		$slug   = 'a' === $cohort ? $config['variation_a'] : $config['variation_b'];

		return array(
			'cohort' => $cohort,
			'slug'   => $slug,
		);
	}

	/**
	 * Balance cohorts toward the configured split using aggregate counters.
	 *
	 * @param int                   $split_a Target percentage for cohort A (0–100).
	 * @param array{a: int, b: int} $counts  Existing assignment totals.
	 * @return 'a'|'b'
	 */
	public static function pick_cohort( int $split_a, array $counts ): string {
		$split_a = max( 0, min( 100, $split_a ) );
		$counts  = array(
			'a' => max( 0, (int) ( $counts['a'] ?? 0 ) ),
			'b' => max( 0, (int) ( $counts['b'] ?? 0 ) ),
		);

		$total = $counts['a'] + $counts['b'];
		if ( 0 === $total ) {
			return self::random_cohort( $split_a );
		}

		$actual_a_pct = ( $counts['a'] / $total ) * 100;

		if ( $actual_a_pct < $split_a ) {
			return 'a';
		}

		if ( $actual_a_pct > $split_a ) {
			return 'b';
		}

		return self::random_cohort( $split_a );
	}

	/**
	 * @param int $split_a Target percentage for cohort A.
	 * @return 'a'|'b'
	 */
	private static function random_cohort( int $split_a ): string {
		if ( function_exists( 'wp_rand' ) ) {
			return wp_rand( 1, 100 ) <= $split_a ? 'a' : 'b';
		}

		return random_int( 1, 100 ) <= $split_a ? 'a' : 'b';
	}
}
