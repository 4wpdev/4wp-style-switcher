<?php
/**
 * User preference scaffold tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\User_Preference\User_Preference;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\User_Preference\User_Preference
 */
class User_PreferenceTest extends TestCase {

	public function test_get_config_returns_scaffold_status(): void {
		$config = User_Preference::get_config();

		$this->assertSame( 'scaffold', $config['status'] );
		$this->assertFalse( $config['enabled'] );
	}

	public function test_is_active_is_false_by_default(): void {
		$this->assertFalse( User_Preference::is_active() );
	}

	public function test_get_for_current_user_returns_empty_when_inactive(): void {
		$this->assertSame( '', User_Preference::get_for_current_user() );
	}
}
