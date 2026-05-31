<?php
/**
 * A/B testing tests.
 *
 * @package ForWP\StyleSwitcher\Tests
 */

namespace ForWP\StyleSwitcher\Tests;

use ForWP\StyleSwitcher\Ab_Testing\Ab_Testing;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ForWP\StyleSwitcher\Ab_Testing\Ab_Testing
 */
class Ab_TestingTest extends TestCase {

	public function test_assign_cohort_returns_null_when_not_ready(): void {
		$this->assertNull( Ab_Testing::assign_cohort() );
	}

	public function test_get_config_includes_split_b(): void {
		$config = Ab_Testing::get_config();

		$this->assertArrayHasKey( 'traffic_split_a', $config );
		$this->assertArrayHasKey( 'traffic_split_b', $config );
		$this->assertSame( 100 - $config['traffic_split_a'], $config['traffic_split_b'] );
		$this->assertArrayHasKey( 'status', $config );
	}

	public function test_pick_cohort_prefers_a_when_below_target(): void {
		$this->assertSame( 'a', Ab_Testing::pick_cohort( 50, array( 'a' => 40, 'b' => 60 ) ) );
	}

	public function test_pick_cohort_prefers_b_when_above_target(): void {
		$this->assertSame( 'b', Ab_Testing::pick_cohort( 50, array( 'a' => 60, 'b' => 40 ) ) );
	}

	public function test_pick_cohort_uses_random_when_balanced(): void {
		$results = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$results[] = Ab_Testing::pick_cohort( 50, array( 'a' => 50, 'b' => 50 ) );
		}

		$this->assertContains( 'a', $results );
		$this->assertContains( 'b', $results );
	}

	public function test_pick_cohort_on_empty_totals(): void {
		$cohort = Ab_Testing::pick_cohort( 100, array( 'a' => 0, 'b' => 0 ) );
		$this->assertContains( $cohort, array( 'a', 'b' ) );
	}
}
