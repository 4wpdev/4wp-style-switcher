<?php
/**
 * Style resolver tests (cookie helper).
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Style_Resolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Style_Resolver
 */
class Style_ResolverTest extends TestCase {

	protected function tearDown(): void {
		unset( $_COOKIE[ Style_Resolver::VISITOR_COOKIE ] );
		parent::tearDown();
	}

	public function test_read_visitor_preference_from_cookie(): void {
		$_COOKIE[ Style_Resolver::VISITOR_COOKIE ] = 'dark-mode';

		$this->assertSame( 'dark-mode', Style_Resolver::read_visitor_preference() );
	}

	public function test_read_visitor_preference_sanitizes(): void {
		$_COOKIE[ Style_Resolver::VISITOR_COOKIE ] = 'Dark Mode';

		$this->assertSame( 'dark-mode', Style_Resolver::read_visitor_preference() );
	}

	public function test_read_visitor_preference_empty_when_missing(): void {
		$this->assertSame( '', Style_Resolver::read_visitor_preference() );
	}
}
