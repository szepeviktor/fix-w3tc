<?php
/**
 * Class W3_Advanced_Cache_Test
 *
 * @package W3_Total_Cache
 */
require_once dirname(dirname(__FILE__)) . '/w3-total-cache-fixed.php';

/**
 * W3_Advanced_Cache_Test Tests
 */
class W3_Advanced_Cache_Test extends WP_UnitTestCase {

	/**
	 * @var W3_Config
	 */
	protected $config;
	
	/**
	 * @var WP_UnitTest_Factory
	 */
	private $factory;
	
	/**
	 * @var WP_Post
	 */
	private $test_post;
	

	
	/**
	 * @see parent::setUp()
	 */
	function setUp() {
	
		if( !is_dir(WP_CONTENT_DIR . "/cache/") ){
			mkdir(WP_CONTENT_DIR . "/cache/", 0777);
			chmod(WP_CONTENT_DIR . "/cache/", 0777);
		}
	
		if( !defined('WP_CACHE') ){
			define('WP_CACHE', true);
		}
		if( !defined('WP_DEBUG') ){
			define('WP_DEBUG', true);
		}
		
		if(WP_CACHE !== true){
			throw new Exception('WP_CACHE not true (needed for enable the cache)');
		}
		if(WP_DEBUG !== true){
			throw new Exception('WP_DEBUG not true (needed for check the cache)');
		}
		
		if( !file_exists(WP_CONTENT_DIR.'/advanced-cache.php') ){
			if( !copy(dirname(dirname(__FILE__)).'/wp-content/advanced-cache.php', WP_CONTENT_DIR.'/advanced-cache.php') ){
				throw new Exception('Failed copy advanced-cache.php in WP_CONTENT_DIR');
			}
		}
		
		$this->config  = w3_instance('W3_Config');
		$this->factory = $this->__get('factory');
		
		// create a post and set the url
		$this->test_post = get_post($this->factory->post->create(array('post_title' => 'Test Title', 'post_content' => 'Test Content'))));
	}
	
	/**
	 * @see parent::tearDown()
	 */
	public function tearDown()
	{
		// flush the cache
		w3tc_pgcache_flush();
	}
	
	/**
	 * Call an URL and return the output
	 * 
	 * @throws \Exception
	 * @return string|false
	 */
	private function call_url()
	{
		static $post_url = null;
		
		if( is_null($post_url) ){
			$post_url = get_permalink($this->test_post->ID);
		}

		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $post_url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($ch);
		
		if ( curl_errno($ch) ){
			throw new Exception(curl_error($ch));
		}
		
		curl_close($ch);
		
		return $result;
	}
	
	/**
	 * Return the cache time from the comment of w3tc debug
	 * @param string $content
	 * @return int
	 */
	private function cache_date($content){
		return strtotime(substr($string, -42, 20)); // 2017-01-06 14:01:59 by W3 Total Cache -->
	}
    
    /**
     * Check the cache
     */
    public function test_cache($data)
    {
        $first_request_time  = $this->cache_date($this->call_url());
        
        $this->assertTrue($first_request_time > 0);
        
        $second_request_time = $this->cache_date($this->call_url());
        
        $this->assertTrue($second_request_time > 0);
        
        $this->assertTrue($first_request_time == $second_request_time);
        
        w3tc_pgcache_flush_post($this->test_post->ID);
        
        $third_request_time = $this->cache_date($this->call_url());
        
        $this->assertTrue($third_request_time > 0);
        
        $this->assertTrue($third_request_time > $first_request_time);
    }
}
