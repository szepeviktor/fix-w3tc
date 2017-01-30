<?php
namespace W3TC;
/**
 * Purge page caches by URL
 */



/**
 * Class Generic_Plugin_PageCachePurge
 */
class Generic_Plugin_PageCachePurge {
	/**
	 * Array of urls
	 *
	 * @var array
	 */
	var $_urls = array();

        /**
	 * Config
	 */
	private $_config = null;

	function __construct() {
		$this->_config = Dispatcher::config();
	}

	/**
	 * Runs plugin
	 */
	function run() {
            if(! $this->_config->get_boolean( 'pgcache.enabled' )) {
                // page cache is not enabled
                return;
            }
            
		if ( Util_Admin::get_current_wp_page() == 'w3tc_dashboard' )
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );


		add_action( 'w3tc_widget_setup', array(
				$this,
				'wp_dashboard_setup'
			) );
		add_action( 'w3tc_network_dashboard_setup', array(
				$this,
				'wp_dashboard_setup'
			) );
                
		if ( is_admin() ) {
			add_action( 'wp_ajax_w3tc_action_purge_urls', array( $this, 'action_purge_urls' ) );
		}

	}

	/**
	 * Dashboard setup action
	 *
	 * @return void
	 */
	function wp_dashboard_setup() {
            
		Util_Widget::add( 'w3tc_page_cache_purge',
			'<div class="w3tc-widget-text">' .
			__( 'Purge Page Caches', 'w3-total-cache' ) .
			'</div>',
			array( $this, 'widget_form' ),
			null, 'normal' );
	}

	function widget_form() {
		include W3TC_INC_WIDGET_DIR . '/pagepurge.php';
	}

	function action_purge_urls() {
                if(!wp_verify_nonce( Util_Request::get_string( 'nonce' ), 'w3tc' )) {
                    return false;
                } 
                
		$urls = Util_Request::get_array( 'purge_urls' );
                $Flusher = new PgCache_Flush();
                
                $cache = $Flusher->_get_cache();
                $mobile_groups = $Flusher->_get_mobile_groups();
                $referrer_groups = $Flusher->_get_referrer_groups();
                $encryptions = $Flusher->_get_encryptions();
                $compressions = $Flusher->_get_compressions();

                foreach ( $urls as $url ) {
                        $Flusher->_flush_url( $url, $cache, $mobile_groups,
                                $referrer_groups, $encryptions, $compressions );
                }
                echo 'URL(s) successfully flushed';
		wp_die();
	}

	public function enqueue() {
		wp_enqueue_style( 'w3tc-widget' );
		wp_enqueue_script( 'w3tc-metadata' );
		wp_enqueue_script( 'w3tc-widget' );
	}
}
