<?php
/**
 * Plugin options.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Admin;

use ForWP\StyleSwitcher\Style_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Option accessors.
 */
final class Settings {

	public const OPTION_KEY = 'forwp_ss_settings';

	/**
	 * @var self|null
	 */
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();

		$settings = wp_parse_args(
			$stored,
			array(
				'visitor_switcher_enabled' => true,
				'default_variation'        => '',
				'switcher_position'        => 'bottom-right',
				'light_dark_mode_enabled'  => false,
				'light_variation'          => '',
				'dark_variation'           => '',
				'visitor_storage_days'     => 365,
			)
		);

		if ( array_key_exists( 'allowed_variations', $stored ) ) {
			$allowed = is_array( $stored['allowed_variations'] )
				? array_values( $stored['allowed_variations'] )
				: array();
			// Empty array means corrupted / accidental save — treat as "all variations allowed".
			$settings['allowed_variations'] = empty( $allowed ) ? null : $allowed;
		} else {
			$settings['allowed_variations'] = null;
		}

		$settings['ab_testing'] = wp_parse_args(
			is_array( $stored['ab_testing'] ?? null ) ? $stored['ab_testing'] : array(),
			array(
				'enabled'         => false,
				'variation_a'     => '',
				'variation_b'     => '',
				'traffic_split_a' => 50,
			)
		);

		$settings['user_preferences'] = wp_parse_args(
			is_array( $stored['user_preferences'] ?? null ) ? $stored['user_preferences'] : array(),
			array(
				'enabled' => false,
			)
		);

		return $settings;
	}

	/**
	 * @param array<string, mixed> $settings Settings payload.
	 */
	public function save( array $settings ): void {
		$merged   = array(
			'visitor_switcher_enabled' => ! empty( $settings['visitor_switcher_enabled'] ),
			'default_variation'        => sanitize_title( (string) ( $settings['default_variation'] ?? '' ) ),
			'switcher_position'        => sanitize_key( (string) ( $settings['switcher_position'] ?? 'bottom-right' ) ),
			'light_dark_mode_enabled'  => ! empty( $settings['light_dark_mode_enabled'] ),
			'light_variation'          => sanitize_title( (string) ( $settings['light_variation'] ?? '' ) ),
			'dark_variation'           => sanitize_title( (string) ( $settings['dark_variation'] ?? '' ) ),
			'visitor_storage_days'     => self::sanitize_storage_days( $settings['visitor_storage_days'] ?? 365 ),
		);
		$existing = get_option( self::OPTION_KEY, array() );
		$existing = is_array( $existing ) ? $existing : array();

		if ( array_key_exists( 'allowed_variations', $settings ) ) {
			$allowed                      = self::sanitize_allowed_slugs( $settings['allowed_variations'] );
			$merged['allowed_variations'] = empty( $allowed ) ? null : $allowed;
		} elseif ( array_key_exists( 'allowed_variations', $existing ) ) {
			$existing_allowed             = is_array( $existing['allowed_variations'] )
				? $existing['allowed_variations']
				: array();
			$merged['allowed_variations'] = empty( $existing_allowed ) ? null : $existing_allowed;
		}

		$allowed_slugs = self::resolve_allowed_slugs( $merged['allowed_variations'] ?? null );
		if ( '' !== $merged['default_variation'] && ! in_array( $merged['default_variation'], $allowed_slugs, true ) ) {
			$merged['default_variation'] = '';
		}

		$merged['light_variation'] = self::sanitize_variation_slug( $merged['light_variation'], $allowed_slugs );
		$merged['dark_variation']  = self::sanitize_variation_slug( $merged['dark_variation'], $allowed_slugs );
		if ( '' !== $merged['light_variation'] && $merged['light_variation'] === $merged['dark_variation'] ) {
			$merged['dark_variation'] = '';
		}
		if ( empty( $merged['light_dark_mode_enabled'] ) || '' === $merged['light_variation'] || '' === $merged['dark_variation'] ) {
			$merged['light_dark_mode_enabled'] = false;
		}

		if ( array_key_exists( 'ab_testing', $settings ) && is_array( $settings['ab_testing'] ) ) {
			$merged['ab_testing'] = self::sanitize_ab_testing( $settings['ab_testing'], $allowed_slugs );
		} elseif ( array_key_exists( 'ab_testing', $existing ) && is_array( $existing['ab_testing'] ) ) {
			$merged['ab_testing'] = $existing['ab_testing'];
		} else {
			$merged['ab_testing'] = self::default_ab_testing();
		}

		if ( array_key_exists( 'user_preferences', $settings ) && is_array( $settings['user_preferences'] ) ) {
			$merged['user_preferences'] = self::sanitize_user_preferences( $settings['user_preferences'] );
		} elseif ( array_key_exists( 'user_preferences', $existing ) && is_array( $existing['user_preferences'] ) ) {
			$merged['user_preferences'] = $existing['user_preferences'];
		} else {
			$merged['user_preferences'] = self::default_user_preferences();
		}

		update_option( self::OPTION_KEY, $merged, false );
	}

	/**
	 * Stored allowed slugs, or null when all theme variations are allowed.
	 *
	 * @return string[]|null
	 */
	public function get_allowed_variations_setting(): ?array {
		$allowed = $this->all()['allowed_variations'] ?? null;

		if ( null === $allowed || ( is_array( $allowed ) && empty( $allowed ) ) ) {
			return null;
		}

		return $allowed;
	}

	/**
	 * One-time fix: empty allowed list in the database → null (all variations).
	 */
	public function migrate_allowed_variations(): void {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) || ! array_key_exists( 'allowed_variations', $stored ) ) {
			return;
		}

		if ( is_array( $stored['allowed_variations'] ) && empty( $stored['allowed_variations'] ) ) {
			$stored['allowed_variations'] = null;
			update_option( self::OPTION_KEY, $stored, false );
		}
	}

	/**
	 * Effective allowed variation slugs for the active theme.
	 *
	 * @return string[]
	 */
	public function get_allowed_variation_slugs(): array {
		return self::resolve_allowed_slugs( $this->get_allowed_variations_setting() );
	}

	/**
	 * @param mixed $slugs Raw slug list from REST or options.
	 * @return string[]
	 */
	private static function sanitize_allowed_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return array();
		}

		$theme_slugs = array_column( Style_Registry::get_theme_variations(), 'slug' );
		$out         = array();

		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( (string) $slug );
			if ( '' === $slug || ! in_array( $slug, $theme_slugs, true ) ) {
				continue;
			}
			$out[] = $slug;
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string[]|null $allowed Stored allowed slugs; null means all theme variations.
	 * @return string[]
	 */
	private static function resolve_allowed_slugs( ?array $allowed ): array {
		$theme_slugs = array_column( Style_Registry::get_theme_variations(), 'slug' );

		if ( null === $allowed ) {
			return $theme_slugs;
		}

		return array_values( array_intersect( $theme_slugs, $allowed ) );
	}

	/**
	 * @param string   $slug          Variation slug.
	 * @param string[] $allowed_slugs Allowed slugs for the active theme.
	 */
	private static function sanitize_variation_slug( string $slug, array $allowed_slugs ): string {
		if ( '' === $slug || ! in_array( $slug, $allowed_slugs, true ) ) {
			return '';
		}

		$theme_slugs = array_column( Style_Registry::get_theme_variations(), 'slug' );

		return in_array( $slug, $theme_slugs, true ) ? $slug : '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_ab_testing_settings(): array {
		return is_array( $this->all()['ab_testing'] ?? null )
			? $this->all()['ab_testing']
			: self::default_ab_testing();
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function default_ab_testing(): array {
		return array(
			'enabled'         => false,
			'variation_a'     => '',
			'variation_b'     => '',
			'traffic_split_a' => 50,
		);
	}

	/**
	 * @param array<string, mixed> $ab      Raw A/B settings.
	 * @param string[]             $allowed Allowed variation slugs.
	 * @return array<string, mixed>
	 */
	private static function sanitize_ab_testing( array $ab, array $allowed ): array {
		$split = isset( $ab['traffic_split_a'] ) ? (int) $ab['traffic_split_a'] : 50;
		$split = max( 0, min( 100, $split ) );

		$variation_a = self::sanitize_variation_slug( sanitize_title( (string) ( $ab['variation_a'] ?? '' ) ), $allowed );
		$variation_b = self::sanitize_variation_slug( sanitize_title( (string) ( $ab['variation_b'] ?? '' ) ), $allowed );

		if ( '' !== $variation_a && $variation_a === $variation_b ) {
			$variation_b = '';
		}

		$enabled = ! empty( $ab['enabled'] );
		if ( $enabled && ( '' === $variation_a || '' === $variation_b ) ) {
			$enabled = false;
		}

		return array(
			'enabled'         => $enabled,
			'variation_a'     => $variation_a,
			'variation_b'     => $variation_b,
			'traffic_split_a' => $split,
		);
	}

	public function is_visitor_switcher_enabled(): bool {
		return ! empty( $this->all()['visitor_switcher_enabled'] );
	}

	public function get_default_variation(): string {
		$slug = sanitize_title( (string) ( $this->all()['default_variation'] ?? '' ) );
		if ( '' === $slug ) {
			return '';
		}

		return in_array( $slug, $this->get_allowed_variation_slugs(), true ) ? $slug : '';
	}

	public function get_switcher_position(): string {
		$pos = sanitize_key( (string) ( $this->all()['switcher_position'] ?? 'bottom-right' ) );

		return in_array( $pos, array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true )
			? $pos
			: 'bottom-right';
	}

	public function get_visitor_storage_days(): int {
		return self::sanitize_storage_days( $this->all()['visitor_storage_days'] ?? 365 );
	}

	/**
	 * @return array{enabled: bool}
	 */
	public function get_user_preferences_settings(): array {
		$raw = $this->all()['user_preferences'] ?? self::default_user_preferences();

		return is_array( $raw ) ? $raw : self::default_user_preferences();
	}

	/**
	 * @return array{enabled: bool}
	 */
	private static function default_user_preferences(): array {
		return array(
			'enabled' => false,
		);
	}

	/**
	 * @param array<string, mixed> $prefs Raw user preference settings.
	 * @return array{enabled: bool}
	 */
	private static function sanitize_user_preferences( array $prefs ): array {
		// Saved for a future release; assignment stays off while scaffolded.
		return array(
			'enabled' => false,
		);
	}

	private static function sanitize_storage_days( $days ): int {
		$days = (int) $days;

		return max( 1, min( 3650, $days ) );
	}

	public function is_light_dark_mode_enabled(): bool {
		return ! empty( $this->all()['light_dark_mode_enabled'] );
	}

	public function get_light_variation(): string {
		return sanitize_title( (string) ( $this->all()['light_variation'] ?? '' ) );
	}

	public function get_dark_variation(): string {
		return sanitize_title( (string) ( $this->all()['dark_variation'] ?? '' ) );
	}

	/**
	 * Light/Dark pair when fully configured and valid.
	 *
	 * @return array{light: array{slug: string, title: string}, dark: array{slug: string, title: string}}|null
	 */
	public function get_light_dark_config(): ?array {
		if ( ! $this->is_light_dark_mode_enabled() ) {
			return null;
		}

		$light_slug = $this->get_light_variation();
		$dark_slug  = $this->get_dark_variation();
		if ( '' === $light_slug || '' === $dark_slug || $light_slug === $dark_slug ) {
			return null;
		}

		$allowed = $this->get_allowed_variation_slugs();
		if ( ! in_array( $light_slug, $allowed, true ) || ! in_array( $dark_slug, $allowed, true ) ) {
			return null;
		}

		$light = Style_Registry::get_variation( $light_slug );
		$dark  = Style_Registry::get_variation( $dark_slug );
		if ( null === $light || null === $dark ) {
			return null;
		}

		return array(
			'light' => $light,
			'dark'  => $dark,
		);
	}

	public function is_light_dark_mode_ready(): bool {
		return null !== $this->get_light_dark_config();
	}
}
