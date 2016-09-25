<?php
/**
 * Class SampleTest
 *
 * @package W3_Total_Cache
 */

require_once dirname(dirname(__FILE__)) . '/w3-total-cache.php';

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * @var W3_Root
	 */
	private $root;
	
	function setUp() {
		$this->root = w3_instance('W3_Root');
		$this->root->run();
	}
	
	/**
	 * A single example test.
	 */
	function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( $this->root instanceof W3_Root );
	}
}

