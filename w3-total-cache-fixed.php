<?php
/*
Plugin Name: W3 Total Cache (Fixed)
Description: A community driven build of W3 Total Cache originally developed by @ftownes. The aim is to continuously incorporate fixes, improvements, and enhancements over the official Wordpress release of W3 Total Cache.
Version: 0.9.4.6.3
Plugin URI: https://github.com/szepeviktor/w3-total-cache-fixed/
Author: W3TC (Fixed) Community
Author URI: https://github.com/szepeviktor/w3-total-cache-fixed/
Network: True
*/

/*  Copyright (c) 2009 Frederick Townes <ftownes@w3-edge.com>
    Portions of this distribution are copyrighted by:
		Copyright (c) 2008 Ryan Grove <ryan@wonko.com>
		Copyright (c) 2008 Steve Clay <steve@mrclay.org>
                Copyright (c) 2007 Matt Mullenweg
                Copyright (c) 2007 Andy Skelton
                Copyright (c) 2007 Iliya Polihronov
                Copyright (c) 2007 Michael Adams
                Copyright (c) 2007 Automattic Inc.
                Ryan Boren
	All rights reserved.

	W3 Total Cache is distributed under the GNU General Public License, Version 2,
	June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
	St, Fifth Floor, Boston, MA 02110, USA

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

if (!defined('ABSPATH')) {
    die();
}

/**
 * Abort W3TC loading if WordPress is upgrading
 */
if (defined('WP_INSTALLING') && WP_INSTALLING)
    return;

//If an existing W3TC is activated we deactivate this plugin and warn about it!
if (is_w3tc_activated())
{
	unset($_GET['activate']);
	return;
}

include dirname(__FILE__) . '/lib/EDD/integration.php';

if (!defined('W3TC_IN_MINIFY')) {
    /**
     * Require plugin configuration
     */
    require_once dirname(__FILE__) . '/inc/define.php';
    
    // Load the wp cli command - if run from wp-cli
    if (defined('WP_CLI') && WP_CLI)
      w3_require_once(W3TC_LIB_W3_DIR . '/Cli.php');

    /**
     * Run
     */
    $root = w3_instance('W3_Root');
    $root->run();
}

function plugin_update_init()
{
	w3_require_once(W3TC_DIR . '/updater.php');
	
	$config = array(
		'slug' 				 => plugin_basename(__FILE__), 	// this is the slug of your plugin
		'proper_folder_name' => W3TC_UPDATER_FOLDER_NAME, 	// this is the name of the folder your plugin lives in
		'api_url' 			 => W3TC_UPDATER_API_URL, 		// the github API url of your github repo
		'raw_url' 			 => W3TC_UPDATER_RAW_URL, 		// the github raw url of your github repo
		'github_url' 		 => W3TC_UPDATER_GITHUB_URL, 	// the github url of your github repo
		'zip_url' 			 => W3TC_UPDATER_ZIP_URL, 		// the zip url of the github repo
		'sslverify' 		 => W3TC_UPDATER_SSLVERIFY, 	// wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
		'requires' 			 => W3TC_UPDATER_REQUIRES, 		// which version of WordPress does your plugin require?
		'tested' 			 => W3TC_TESTED_ON_WP_VERSION, 	// which version of WordPress is your plugin tested up to?
		'banner' 			 => W3TC_UPDATER_BANNER_URL,	// banner image to show on the View Details popup
		'changelog' 		 => W3TC_UPDATER_CHANGELOG,		// the changelog file to show on the View Details's Changelog tab
	);

	new WP_GitHub_Updater($config);
}
add_action( 'init', 'plugin_update_init' );

// Checks if there is an existing W3TC version running. If so, it deactivates itself 
function is_w3tc_activated()
{
	if (!function_exists('get_plugin_data')) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$me= plugin_basename(__FILE__);
	$plugin_list = get_plugins();

	foreach ($plugin_list as $plugin_file=>$plugin_data) {	
		if (is_plugin_active($plugin_file) && $plugin_file != $me) {
			if (stripos(strrev($plugin_file), 'php.ehcac-latot-3w') === 0 && stripos($plugin_data['Name'],'W3 Total Cache') !== false) {
				add_action('admin_notices',	create_function('','echo \'<div id="message" class="error"><p>Sorry, <i><strong>W3 Total Cache (Fixed)</strong></i> has been blocked from being activated because an existing install of W3 Total Cache (version ' . $plugin_data['Version'] . ') was detected. Please deactivate that install and try again.</p></div>\';'));
				deactivate_plugins($me); //Deactivate this plugin
				return true;
			}
		}
	}

	return false;
}
