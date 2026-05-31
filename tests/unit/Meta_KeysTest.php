<?php
/**
 * Meta keys tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Meta_Keys;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Meta_Keys
 */
class Meta_KeysTest extends TestCase {

	public function test_page_style_meta_key(): void {
		$this->assertSame( '_forwp_ss_page_style', Meta_Keys::PAGE_STYLE_SLUG );
	}

	public function test_page_style_locked_meta_key(): void {
		$this->assertSame( '_forwp_ss_page_style_locked', Meta_Keys::PAGE_STYLE_LOCKED );
	}

	public function test_default_post_types(): void {
		$this->assertSame( array( 'page', 'post' ), Meta_Keys::post_types() );
	}
}
