<?php
/**
 * Settings tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Admin\Settings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Admin\Settings
 */
class SettingsTest extends TestCase {

	public function test_sanitize_allowed_slugs_filters_unknown(): void {
		$method = new \ReflectionMethod( Settings::class, 'sanitize_allowed_slugs' );
		$method->setAccessible( true );

		$result = $method->invoke( null, array( 'evening', 'not-a-theme-variation', 'Evening' ) );

		$this->assertSame( array(), $result );
	}

	public function test_resolve_allowed_slugs_null_means_all_theme_slugs(): void {
		$method = new \ReflectionMethod( Settings::class, 'resolve_allowed_slugs' );
		$method->setAccessible( true );

		$result = $method->invoke( null, null );

		$this->assertSame( array(), $result );
	}

	public function test_resolve_allowed_slugs_intersects_with_theme(): void {
		$method = new \ReflectionMethod( Settings::class, 'resolve_allowed_slugs' );
		$method->setAccessible( true );

		$result = $method->invoke( null, array( 'evening', 'missing' ) );

		$this->assertSame( array(), $result );
	}

	public function test_sanitize_variation_slug_rejects_unknown(): void {
		$method = new \ReflectionMethod( Settings::class, 'sanitize_variation_slug' );
		$method->setAccessible( true );

		$result = $method->invoke( null, 'evening', array( 'evening' ) );

		$this->assertSame( '', $result );
	}

	public function test_sanitize_storage_days_clamps_range(): void {
		$method = new \ReflectionMethod( Settings::class, 'sanitize_storage_days' );
		$method->setAccessible( true );

		$this->assertSame( 1, $method->invoke( null, 0 ) );
		$this->assertSame( 3650, $method->invoke( null, 9999 ) );
		$this->assertSame( 90, $method->invoke( null, 90 ) );
	}

	public function test_sanitize_user_preferences_stays_disabled(): void {
		$method = new \ReflectionMethod( Settings::class, 'sanitize_user_preferences' );
		$method->setAccessible( true );

		$result = $method->invoke( null, array( 'enabled' => true ) );

		$this->assertSame( array( 'enabled' => false ), $result );
	}
}
