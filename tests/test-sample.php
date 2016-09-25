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
	
	public function tearDown()
	{
		wp_cache_flush();
	}
	
	/**
	 * Test for test environment.
	 */
	function test_index() {
		$this->go_to('/');
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_add($data)
	{
		$group = 'group';
		$key = 0;
	
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
		$this->assertFalse(wp_cache_add($key, 'test', $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
	
	}
	
	public function test_wp_cache_close()
	{
		$this->assertTrue(wp_cache_close());
	}
	
	public function test_wp_cache_decr()
	{
		$key = 'key';
		$group = 'group';
	
		$this->assertTrue(wp_cache_add($key, 10, $group));
		$this->assertEquals(7, wp_cache_decr($key, 3, $group));
		$this->assertEquals(6, wp_cache_decr($key, 1, $group));
		$this->assertEquals(6, wp_cache_get($key, $group));
		$this->assertEquals(0, wp_cache_decr($key, 45, $group));
		$this->assertEquals(0, wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_delete($data)
	{
		$group = 'group';
		$key = 0;
	
		$this->assertFalse(wp_cache_delete($key, $group));
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertTrue(wp_cache_delete($key, $group));
		$this->assertFalse(wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_flush($data)
	{
		$group = 'group';
		$key = 0;
	
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertTrue(wp_cache_flush());
		$this->assertFalse(wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_get($data)
	{
		// TODO: test force parameter
		$group = 'group';
		$key = 0;
	
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
		$this->assertEquals($data, wp_cache_get($key, $group, false, $found));
		$this->assertTrue($found);
	
		$this->assertFalse(wp_cache_get('test', $group));
		$this->assertFalse(wp_cache_get('test', $group, false, $found));
		$this->assertFalse($found);
	}
	
	public function test_wp_cache_incr()
	{
		$key = 'key';
		$group = 'group';
	
		$this->assertTrue(wp_cache_add($key, 10, $group));
		$this->assertEquals(13, wp_cache_incr($key, 3, $group));
		$this->assertEquals(14, wp_cache_incr($key, 1, $group));
		$this->assertEquals(14, wp_cache_get($key, $group));
		$this->assertEquals(0, wp_cache_incr($key, -45, $group));
		$this->assertEquals(0, wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_replace($data)
	{
		$group = 'group';
		$key = 0;
	
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertTrue(wp_cache_replace($key, 'test', $group));
		$this->assertEquals('test', wp_cache_get($key, $group));
		$this->assertFalse(wp_cache_replace('nokey', $data, $group));
		$this->assertFalse(wp_cache_get('nokey', $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_set($data)
	{
		$group = 'group';
		$key = 0;
	
		$this->assertTrue(wp_cache_set($key, $data, $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_switch_to_blog($data)
	{
		if( !is_multisite() ){
			return;	
		}
		
		$group = 'group';
		$key   = 'foo';
		
		$this->assertNull(wp_cache_switch_to_blog(2));
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
		
		$this->assertNull(wp_cache_switch_to_blog(3));
		$this->assertFalse(wp_cache_get($key, $group));
		$this->assertNull(wp_cache_switch_to_blog(2));
		
		$this->assertEquals($data, wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_add_global_groups($data)
	{
		if( !is_multisite() ){
			return;	
		}
		
		$group = 'group';
		$key = 'foo';
	
		$this->assertNull(wp_cache_add_global_groups($group));
		$this->assertNull(wp_cache_switch_to_blog(2));
		$this->assertTrue(wp_cache_add($key, $data, $group));
		$this->assertEquals($data, wp_cache_get($key, $group));
		$this->assertNull(wp_cache_switch_to_blog(3));
		$this->assertEquals($data, wp_cache_get($key, $group));
		$this->assertNull(wp_cache_switch_to_blog(2));
		$this->assertEquals($data, wp_cache_get($key, $group));
	}
	
	/**
	 * @param mixed $data
	 * @dataProvider getTestData
	 */
	public function test_wp_cache_add_non_persistent_groups($data)
	{
		$key = 'foo';
	
		$this->assertNull(wp_cache_add_non_persistent_groups('temp'));
		$this->assertTrue(wp_cache_add($key, $data, 'temp'));
		$this->assertEquals($data, wp_cache_get($key, 'temp'));
		$this->assertTrue(wp_cache_add($key, $data, 'group'));
		$this->assertEquals($data, wp_cache_get($key, 'group'));
		// Re-init the cache. This deletes the local cache but keeps the persistent one
		wp_cache_init();
		$this->assertFalse(wp_cache_get($key, 'temp'));
		$this->assertEquals($data, wp_cache_get($key, 'group'));
	}
	
	public function test_groups()
	{
		$key = 'foo';
		$this->assertTrue(wp_cache_add($key, 'test1', 'group1'));
		$this->assertTrue(wp_cache_add($key, 'test2', 'group2'));
		$this->assertEquals('test1', wp_cache_get($key, 'group1'));
		$this->assertEquals('test2', wp_cache_get($key, 'group2'));
	
		$this->assertTrue(wp_cache_set($key, 'test12', 'group1'));
		$this->assertTrue(wp_cache_set($key, 'test22', 'group2'));
		$this->assertEquals('test12', wp_cache_get($key, 'group1'));
		$this->assertEquals('test22', wp_cache_get($key, 'group2'));
	
		$this->assertTrue(wp_cache_delete($key, 'group2'));
		$this->assertFalse(wp_cache_get($key, 'group2'));
		$this->assertEquals('test12', wp_cache_get($key, 'group1'));
	
		$this->assertTrue(wp_cache_replace($key, 'test13', 'group1'));
		$this->assertEquals('test13', wp_cache_get($key, 'group1'));
		$this->assertFalse(wp_cache_get($key, 'group2'));
	}
	
	/**
	 * @return array
	 */
	public function getTestData()
	{
		return array(
			array('foo'),
			array(43),
			array(1.234),
			array(1.2e3),
			array(7E-10),
			array(0123),
			array(-123),
			array(0x1A),
			array(array('a', 'b')),
			array(array('foo' => array('a', 'b'))),
			array(null),
			array(false),
			array(true),
			array(new ArrayObject(array('foo', 'bar'))),
			array(new StdClass())
		);
	}
}
Contact GitHub API Training Shop Blog About
© 2016 GitHub, Inc. Terms Privacy Security Status Help