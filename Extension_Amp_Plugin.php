<?php
namespace W3TC;

class Extension_Amp_Plugin {
	function __construct() {
		$is_amp_endpoint = null;
	}

	public function run() {
		add_filter( 'w3tc_minify_js_enable',
			array( $this, 'w3tc_minify_jscss_enable' ) );
		add_filter( 'w3tc_minify_css_enable',
			array( $this, 'w3tc_minify_jscss_enable' ) );
		add_filter( 'w3tc_footer_comment', 
			array( $this, 'w3tc_footer_comment' ) );

	}



	private function is_amp_endpoint() {
		
		if ( function_exists('is_amp_endpoint') ) {
			return is_amp_endpoint();
		}

		return null;
	}



	public function w3tc_minify_jscss_enable( $enabled ) {
		$is_amp_endpoint = $this->is_amp_endpoint();

		if ( !is_null( $is_amp_endpoint ) && $is_amp_endpoint ) {
			// amp has own rules for CSS and JS files, don't touch them by default
			return false;
		}

		return $enabled;
	}



	public function w3tc_footer_comment( $strings ) {
		$is_amp_endpoint = $this->is_amp_endpoint();

		if ( !is_null( $is_amp_endpoint ) && $is_amp_endpoint ) {
			$strings[] = 'AMP page, minification is limited';
		}
		
		return $strings;
	}
}



$p = new Extension_Amp_Plugin();
$p->run();

if ( is_admin() ) {
	$p = new Extension_Amp_Plugin_Admin();
	$p->run();
}
