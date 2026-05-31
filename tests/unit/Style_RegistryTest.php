<?php
/**
 * Style registry tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Style_Registry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Style_Registry
 */
class Style_RegistryTest extends TestCase {

	public function test_dedupe_variations_by_slug_keeps_first_occurrence(): void {
		$method = new \ReflectionMethod( Style_Registry::class, 'dedupe_variations_by_slug' );
		$method->setAccessible( true );

		$raw = array(
			array(
				'title'    => 'Evening',
				'settings' => array( 'color' => array(), 'typography' => array() ),
			),
			array(
				'title'    => 'Evening',
				'settings' => array( 'color' => array() ),
			),
			array(
				'title' => 'Noon',
			),
		);

		$result = $method->invoke( null, $raw );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Evening', $result[0]['title'] );
		$this->assertSame( 'Noon', $result[1]['title'] );
	}
}
