<?php
/**
 * Class W3_Object_Cache_APCu_Test
 *
 * @package W3_Total_Cache
 */
require_once dirname(__FILE__) . '/w3-object-cache-test.php';

/**
 * W3_Object_Cache_APCu_Test Tests
 */
class W3_Object_Cache_APCu_Test extends W3_Object_Cache_Test {
    
    /**
     * @see parent::setUp()
     */
    function setUp() {
        
       parent::setUp();
       
       if( !function_exists('apcu_store') ){
       		$this->markTestSkipped('all tests in W3_Object_Cache_APCu_Test are invactive because APCu module not exists!');
       		return;
       	}
        
       // set up for disk driver
       $this->config->set('dbcache.enabled', true);
       $this->config->set('objectcache.engine', 'apcu');
        
       $this->config->set('objectcache.enabled', true);
       $this->config->set('objectcache.engine', 'apcu');
       
       $this->config->set('pgcache.enabled', true);
       $this->config->set('pgcache.engine','apcu');
        
       $this->config->save();
    }
    
    /**
     * @see parent::tearDown()
     */
    function tearDown() {
    	parent::tearDown();
    }
}
