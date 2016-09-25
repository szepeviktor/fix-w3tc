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
	
	/**
	 * @var W3_Config
	 */
	private $config;
	
	
	function setUp() {
		
		if( !is_dir(WP_CONTENT_DIR . "/cache/") ){
			mkdir(WP_CONTENT_DIR . "/cache/", 0777);
			chmod(WP_CONTENT_DIR . "/cache/", 0777);
		}
		
		$this->root   = w3_instance('W3_Root');
		$this->config = w3_instance('W3_Config');
		
		$this->config->set('dbcache.enabled', true);
		$this->config->set('objectcache.engine', 'file');
		
		$this->config->set('objectcache.enabled', true);
		$this->config->set('objectcache.engine', 'file');
		
		
		$this->config->set('pgcache.enabled', true);
		$this->config->set('pgcache.engine','file_generic');
				
		$this->config->set('minify.enabled', true);
		$this->config->set('minify.css.enable', true);
		$this->config->set('minify.js.enable', true);
		$this->config->set('minify.html.enable', true);
		
		$this->config->save();
	}
	
	public function test_sample()
	{
	    $this->assertTrue(true);
	}
}
