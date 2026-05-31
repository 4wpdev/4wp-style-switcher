<?php
/**
 * Style applicator tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Frontend\Style_Applicator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Frontend\Style_Applicator
 */
class Style_ApplicatorTest extends TestCase {

	public function test_prepare_variation_for_merge_strips_metadata(): void {
		$method = new \ReflectionMethod( Style_Applicator::class, 'prepare_variation_for_merge' );
		$method->setAccessible( true );

		$prepared = $method->invoke(
			null,
			array(
				'$schema'  => 'https://schemas.wp.org/wp/6.7/theme.json',
				'version'  => 3,
				'title'    => 'Midnight',
				'slug'     => 'midnight',
				'settings' => array(
					'color' => array(
						'palette' => array(
							array(
								'color' => '#000000',
								'name'  => 'Base',
								'slug'  => 'base',
							),
						),
					),
				),
			)
		);

		$this->assertArrayNotHasKey( '$schema', $prepared );
		$this->assertArrayNotHasKey( 'title', $prepared );
		$this->assertArrayNotHasKey( 'slug', $prepared );
		$this->assertSame( 3, $prepared['version'] );
		$this->assertSame( '#000000', $prepared['settings']['color']['palette'][0]['color'] );
	}
}
