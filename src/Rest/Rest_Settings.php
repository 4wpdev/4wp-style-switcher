<?php
/**
 * REST API — settings and style registry.
 *
 * @package ForWP\StyleSwitcher
 */

namespace ForWP\StyleSwitcher\Rest;

use ForWP\StyleSwitcher\Admin\Settings;
use ForWP\StyleSwitcher\Ab_Testing\Ab_Testing;
use ForWP\StyleSwitcher\Block_Theme_Guard;
use ForWP\StyleSwitcher\Style_Registry;
use ForWP\StyleSwitcher\User_Preference\User_Preference;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Settings routes.
 */
final class Rest_Settings {

	private const NAMESPACE = 'forwp-style-switcher/v1';

	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_settings' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'update_settings' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/variations',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_variations' ),
					'permission_callback' => array( self::class, 'can_edit' ),
				),
			)
		);
	}

	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function can_edit(): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function get_settings(): WP_REST_Response {
		$settings = Settings::instance()->all();

		return new WP_REST_Response(
			array(
				'settings'         => $settings,
				'is_block_theme'   => Block_Theme_Guard::is_supported(),
				'theme_variations' => Style_Registry::get_theme_variations(),
				'variations'       => Style_Registry::get_variations(),
				'ab_testing'       => Ab_Testing::get_config(),
				'ab_stats'         => Ab_Testing::get_stats_summary(),
				'user_preferences' => User_Preference::get_config(),
				'visitor_storage'  => array(
					'days' => Settings::instance()->get_visitor_storage_days(),
				),
			),
			200
		);
	}

	public static function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		Settings::instance()->save( $params );

		return self::get_settings();
	}

	public static function get_variations(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'variations'     => Style_Registry::get_variations(),
				'is_block_theme' => Block_Theme_Guard::is_supported(),
			),
			200
		);
	}
}
