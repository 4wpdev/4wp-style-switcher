<?php
/**
 * Analytics event bridge — no built-in reporting.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Analytics;

use ForWP\StyleSwitcher\Ab_Testing\Ab_Testing;

defined( 'ABSPATH' ) || exit;

/**
 * Dispatches analytics events to JS and PHP hooks for GA4, GTM, etc.
 */
final class Analytics {

	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'forwp_style_switcher_ab_assigned', array( self::class, 'on_ab_assigned' ), 10, 2 );
	}

	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'forwp-ss-analytics',
			FORWP_STYLE_SWITCHER_URL . 'assets/analytics.js',
			array(),
			FORWP_STYLE_SWITCHER_VERSION,
			true
		);

		wp_localize_script(
			'forwp-ss-analytics',
			'forwpStyleSwitcherAnalyticsConfig',
			self::get_public_config()
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_public_config(): array {
		return array(
			'enabled'   => true,
			'abStatus'  => Ab_Testing::get_config()['status'],
			'eventName' => 'forwp_style_switcher',
		);
	}

	/**
	 * @param array<string, mixed> $payload Event payload.
	 * @return array<string, mixed>
	 */
	public static function track( string $event, array $payload = array() ): array {
		$data = array_merge(
			array(
				'event'     => $event,
				'timestamp' => time(),
			),
			$payload
		);

		/**
		 * Filter analytics payload before dispatch.
		 *
		 * @param array<string, mixed> $data  Event data.
		 * @param string               $event Event name.
		 */
		$data = apply_filters( 'forwp_style_switcher_analytics_event', $data, $event );

		/**
		 * Fires when a style switcher analytics event should be recorded.
		 *
		 * Use for server-side integrations (custom logging, webhooks, etc.).
		 *
		 * @param array<string, mixed> $data  Event data.
		 * @param string               $event Event name.
		 */
		do_action( 'forwp_style_switcher_analytics_track', $data, $event );

		return $data;
	}

	/**
	 * @param array{cohort: string, slug: string} $result Assignment.
	 * @param array<string, mixed>              $config A/B settings.
	 */
	public static function on_ab_assigned( array $result, array $config ): void {
		self::track(
			'ab_assigned',
			array(
				'cohort'    => $result['cohort'],
				'variation' => $result['slug'],
				'split_a'   => $config['traffic_split_a'] ?? null,
				'split_b'   => $config['traffic_split_b'] ?? null,
			)
		);
	}
}
