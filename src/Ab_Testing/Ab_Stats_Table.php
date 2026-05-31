<?php
/**
 * Minimal A/B assignment counters (daily aggregates).
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Ab_Testing;

defined( 'ABSPATH' ) || exit;

/**
 * Stores cohort assignment counts per day for traffic split monitoring.
 */
final class Ab_Stats_Table {

	public const DB_VERSION     = '1';
	public const OPTION_VERSION = 'forwp_ss_ab_db_version';
	public const TABLE_SUFFIX   = 'forwp_ss_ab_stats';

	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	public static function maybe_install(): void {
		if ( self::DB_VERSION === get_option( self::OPTION_VERSION, '' ) && self::exists() ) {
			return;
		}

		self::install();
	}

	public static function exists(): bool {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $table === $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);
	}

	public static function install(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			stat_date DATE NOT NULL,
			cohort CHAR(1) NOT NULL,
			assignments INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (stat_date, cohort),
			KEY stat_date (stat_date)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::OPTION_VERSION, self::DB_VERSION, false );
	}

	/**
	 * @param 'a'|'b' $cohort Assigned cohort.
	 */
	public static function record_assignment( string $cohort ): void {
		global $wpdb;

		if ( ! self::exists() ) {
			self::install();
		}

		$cohort = 'b' === $cohort ? 'b' : 'a';
		$table  = self::table_name();
		$date   = gmdate( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (stat_date, cohort, assignments) VALUES (%s, %s, 1)
				ON DUPLICATE KEY UPDATE assignments = assignments + 1",
				$date,
				$cohort
			)
		);
	}

	/**
	 * @return array{a: int, b: int}
	 */
	public static function get_totals( ?string $since_date = null ): array {
		global $wpdb;

		$totals = array(
			'a' => 0,
			'b' => 0,
		);

		if ( ! self::exists() ) {
			return $totals;
		}

		$table = self::table_name();

		if ( null !== $since_date && '' !== $since_date ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT cohort, SUM(assignments) AS total FROM {$table} WHERE stat_date >= %s GROUP BY cohort",
					$since_date
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				"SELECT cohort, SUM(assignments) AS total FROM {$table} GROUP BY cohort",
				ARRAY_A
			);
		}

		if ( ! is_array( $rows ) ) {
			return $totals;
		}

		foreach ( $rows as $row ) {
			$cohort = isset( $row['cohort'] ) ? (string) $row['cohort'] : '';
			if ( isset( $totals[ $cohort ] ) ) {
				$totals[ $cohort ] = (int) ( $row['total'] ?? 0 );
			}
		}

		return $totals;
	}

	/**
	 * @return array{
	 *   today: array{a: int, b: int, split_a: float|null},
	 *   last_7_days: array{a: int, b: int, split_a: float|null},
	 *   all_time: array{a: int, b: int, split_a: float|null}
	 * }
	 */
	public static function get_summary(): array {
		$today    = gmdate( 'Y-m-d' );
		$week_ago = gmdate( 'Y-m-d', strtotime( '-6 days' ) );

		return array(
			'today'       => self::format_totals( self::get_totals( $today ) ),
			'last_7_days' => self::format_totals( self::get_totals( $week_ago ) ),
			'all_time'    => self::format_totals( self::get_totals() ),
		);
	}

	/**
	 * @param array{a: int, b: int} $counts Raw counts.
	 * @return array{a: int, b: int, split_a: float|null}
	 */
	private static function format_totals( array $counts ): array {
		$total = (int) $counts['a'] + (int) $counts['b'];

		return array(
			'a'       => (int) $counts['a'],
			'b'       => (int) $counts['b'],
			'split_a' => $total > 0 ? round( ( $counts['a'] / $total ) * 100, 1 ) : null,
		);
	}
}
