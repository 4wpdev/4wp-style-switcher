<?php
/**
 * Logged-in user preference (scaffold — not active yet).
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\User_Preference;

use ForWP\StyleSwitcher\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Future per-user variation storage in user meta.
 */
final class User_Preference {

	public const META_KEY = '_forwp_ss_user_style';

	public const STATUS = 'scaffold';

	/**
	 * @return array{enabled: bool, status: string}
	 */
	public static function get_config(): array {
		$raw = Settings::instance()->get_user_preferences_settings();

		return array(
			'enabled' => ! empty( $raw['enabled'] ),
			'status'  => self::STATUS,
		);
	}

	public static function is_active(): bool {
		$config = self::get_config();

		/**
		 * Filter whether logged-in user preferences are active.
		 *
		 * @param bool $active Default false while status is scaffold.
		 */
		return (bool) apply_filters( 'forwp_style_switcher_user_preference_enabled', false ) && $config['enabled'];
	}

	/**
	 * Read saved style for the current user. Not implemented in scaffold phase.
	 */
	public static function get_for_current_user(): string {
		if ( ! self::is_active() || ! is_user_logged_in() ) {
			return '';
		}

		$slug = sanitize_title( (string) get_user_meta( get_current_user_id(), self::META_KEY, true ) );

		return $slug;
	}
}
