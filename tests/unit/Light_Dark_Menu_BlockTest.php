<?php
/**
 * Light / Dark menu block tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Blocks\Light_Dark_Menu_Block;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Blocks\Light_Dark_Menu_Block
 */
class Light_Dark_Menu_BlockTest extends TestCase {

	public function test_block_name(): void {
		$this->assertSame( 'forwp-style-switcher/light-dark-toggle', Light_Dark_Menu_Block::BLOCK_NAME );
	}

	public function test_add_to_navigation_list(): void {
		$result = Light_Dark_Menu_Block::add_to_navigation_list( array( 'core/site-logo' ) );

		$this->assertContains( 'forwp-style-switcher/light-dark-toggle', $result );
		$this->assertContains( 'core/site-logo', $result );
	}
}
