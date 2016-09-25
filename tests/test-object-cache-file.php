<?php
/**
 * Class W3_Object_Cache_File_Test
 *
 * @package W3_Total_Cache
 */
require_once dirname(__FILE__) . '/w3-object-cache-test.php';

/**
 * W3_Object_Cache_File_Test Tests
 */
class W3_Object_Cache_File_Test extends W3_Object_Cache_Test {
    
    /**
     * @see parent::setUp()
     */
    function setUp() {
        
       parent::setUp();
        
       // set up for disk driver
       $this->config->set('dbcache.enabled', true);
       $this->config->set('objectcache.engine', 'file');
        
       $this->config->set('objectcache.enabled', true);
       $this->config->set('objectcache.engine', 'file');
       
       $this->config->set('pgcache.enabled', true);
       $this->config->set('pgcache.engine','file_generic');
        
       $this->config->save();
    }
    
    /**
     * @see parent::tearDown()
     */
    function tearDown() {
    	parent::tearDown();
    }
}
