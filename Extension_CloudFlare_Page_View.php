<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();
?>
<p id="w3tc-options-menu">
    Jump to:
    <a href="admin.php?page=w3tc_general"><?php _e( 'Main Menu', 'w3-total-cache' ); ?></a> |
    <a href="admin.php?page=w3tc_extensions"><?php _e( 'Extensions', 'w3-total-cache' ); ?></a> |
    <a href="#credentials"><?php _e( 'Credentials', 'w3-total-cache' ); ?></a> |
    <a href="#general"><?php _e( 'General', 'w3-total-cache' ); ?></a> |
    <a href="#info"><?php _e( 'Information', 'w3-total-cache' ); ?></a>
</p>
<p>
    CloudFlare extension is currently <?php
if ( $config->is_extension_active_frontend( 'cloudflare' ) )
	echo '<span class="w3tc-enabled">enabled</span>';
else
	echo '<span class="w3tc-disabled">disabled</span>';
?>.
<p>

<form action="admin.php?page=w3tc_extensions&amp;extension=cloudflare&amp;action=view" method="post">
    <p>
        <?php echo Util_Ui::nonce_field( 'w3tc' ); ?>
        <input type="submit" name="w3tc_cloudflare_flush" value="<?php _e( 'Purge CloudFlare cache', 'w3-total-cache' ) ?>" class="button" />
        <?php _e( 'if needed.', 'w3-total-cache' ) ?>
    </p>
</form>

<form action="admin.php?page=w3tc_extensions&amp;extension=cloudflare&amp;action=view" method="post">
    <div class="metabox-holder">
        <?php Util_Ui::postbox_header( __( 'Credentials', 'w3-total-cache' ), '', 'credentials' ); ?>
        <table class="form-table">
			<tr>
				<th style="width: 300px;">
					<label>
<?php
_e( 'Specify account credentials:',
	'w3-total-cache' );
?>
					</label>
				</th>
				<td>
					<?php if ( $state != 'not_configured' ): ?>
						<input class="w3tc_extension_cloudflare_authorize button-primary"
							type="button"
							value="<?php _e( 'Reauthorize', 'w3-total-cache' ); ?>"
							/>
					<?php else: ?>
						<input class="w3tc_extension_cloudflare_authorize button-primary"
							type="button"
							value="<?php _e( 'Authorize', 'w3-total-cache' ); ?>"
							/>
					<?php endif ?>
				</td>
			</tr>

			<?php if ( $state != 'not_configured' ): ?>
			<tr>
				<th>
					<label><?php _e( 'Zone:', 'w3-total-cache' ); ?></label>
				</th>
				<td class="w3tc_config_value_text">
<?php echo $config->get_string( array( 'cloudflare', 'zone_name' ) ) ?>
				</td>
			</tr>
			<?php endif ?>
        </table>


<?php Util_Ui::postbox_footer(); ?>
<?php Util_Ui::postbox_header( __( 'General', 'w3-total-cache' ), '', 'general' ); ?>


<?php if ( $state == 'not_configured' ): ?>
<table class="form-table">
<tr><td colspan="2">
Authenticate your account in order to access settings.
</td></tr>
</table>
<?php endif ?>


<?php if ( $state == 'not_available' ): ?>
<table class="form-table">
<tr><td colspan="2">
CloudFlare not available: <?php echo $error_message; ?>
</td></tr>
</table>
<?php endif ?>


<?php if ( $state == 'available' ): ?>
<table class="form-table">
<?php

		Util_Ui::config_item( array(
				'key' => array( 'cloudflare', 'widget_interval' ),
				'label' => __( 'Widget statistics interval:', 'w3-total-cache' ),
				'control' => 'selectbox',
				'selectbox_values' => array(
					'-30' => 'Last 30 minutes',
					'-360' => 'Last 6 hours',
					'-720' => 'Last 12 hours',
					'-1440' => 'Last 24 hours',
					'-10080' => 'Last week',
					'-43200' => 'Last month'
				)
			)
		);

		Util_Ui::config_item( array(
				'key' => array( 'cloudflare', 'widget_cache_mins' ),
				'label' => __( 'Cache time:', 'w3-total-cache' ),
				'control' => 'textbox',
				'description' =>
				'How many minutes data retrieved from CloudFlare should be stored. Minimum is 1 minute.'
			)
		);

		Util_Ui::config_item( array(
				'key' => array( 'cloudflare', 'pagecache' ),
				'label' => __( 'Page caching:', 'w3-total-cache' ),
				'control' => 'checkbox',
				'checkbox_label' => 'Flush CloudFlare on Post Modifications:',
				'description' =>
				'Enable when you have html pages cached on CloudFlare level.'
			)
		);

?>
</table>
<?php endif; ?>


<?php Util_Ui::button_config_save( 'extension_cloudflare_general' ); ?>
<?php Util_Ui::postbox_footer(); ?>


<?php if ( $state == 'available' ): ?>
<?php Util_Ui::postbox_header( __( 'CloudFlare: Caching', 'w3-total-cache' ), '', 'general' ); ?>
<table class="form-table">

<?php
self::cloudflare_checkbox( $settings, array(
		'key' => 'development_mode',
		'label' => 'Development mode:',
		'description' => 'Development Mode temporarily allows you to enter development mode for your websites if you need to make changes to your site. This will bypass CloudFlare\'s accelerated cache and slow down your site, but is useful if you are making changes to cacheable content (like images, css, or JavaScript) and would like to see those changes right away.'
	) );
self::cloudflare_selectbox( $settings, array(
		'key' => 'cache_level',
		'label' => __( 'Cache level:', 'w3-total-cache' ),
		'values' => array(
			'' => '',
			'aggressive' => 'Aggressive (cache all static resources, including ones with a query string)',
			'basic' => 'Basic (cache most static resources (i.e., css, images, and JavaScript)',
			'simplified' => 'Simplified (ignore the query string when delivering a cached resource)'
		),
		'description' => 'How the content is cached by CloudFlare'
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'sort_query_string_for_cache',
		'label' => 'Query string sorting:',
		'description' => 'CloudFlare will treat files with the same query strings as the same file in cache, regardless of the order of the query strings.',
	) );
self::cloudflare_selectbox( $settings, array(
		'key' => 'browser_cache_ttl',
		'label' => 'Browser cache <acronym title="Time-to-Live ">TTL</acronym>:',
		'values' => array(
			'' => '',
			'30' => '30',
			'60' => '60',
			'300' => '300',
			'1200' => '1200',
			'1800' => '1800',
			'3600' => '3600',
			'7200' => '7200',
			'10800' => '10800',
			'14400' => '14400',
			'18000' => '18000',
			'28800' => '28800',
			'43200' => '43200',
			'57600' => '57600',
			'72000' => '72000',
			'86400' => '86400',
			'172800' => '172800',
			'259200' => '259200',
			'345600' => '345600',
			'432000' => '432000',
			'691200' => '691200',
			'1382400' => '1382400',
			'2073600' => '2073600',
			'2678400' => '2678400',
			'5356800' => '5356800',
			'16070400' => '16070400',
			'31536000' => '31536000'
		),
		'description' => 'Browser cache <acronym title="Time-to-Live ">TTL</acronym> (in seconds) specifies how long CloudFlare-cached resources will remain on your visitors\' computers.',
	) );
self::cloudflare_selectbox( $settings, array(
		'key' => 'challenge_ttl',
		'label' => 'Challenge <acronym title="Time-to-Live ">TTL</acronym>:',
		'values' => array(
			'' => '',
			'300' => '300',
			'900' => '900',
			'1800' => '1800',
			'2700' => '2700',
			'3600' => '3600',
			'7200' => '7200',
			'10800' => '10800',
			'14400' => '14400',
			'28800' => '28800',
			'57600' => '57600',
			'86400' => '86400',
			'604800' => '604800',
			'2592000' => '2592000',
			'31536000' => '31536000'
		),
		'description' => 'Specify how long a visitor is allowed access to your site after successfully completing a challenge (such as a CAPTCHA). After the <acronym title="Time-to-Live ">TTL</acronym> has expired the visitor will have to complete a new challenge.',
	) );
self::cloudflare_selectbox( $settings, array(
		'key' => 'edge_cache_ttl',
		'label' => 'Edge cache TTL:',
		'values' => array(
			'' => '',
			'300' => '300',
			'900' => '900',
			'1800' => '1800',
			'2700' => '2700',
			'3600' => '3600',
			'7200' => '7200',
			'10800' => '10800',
			'14400' => '14400',
			'28800' => '28800',
			'57600' => '57600',
			'86400' => '86400',
			'604800' => '604800',
			'2592000' => '2592000',
			'31536000' => '31536000'
		),
		'description' => 'Controls how long CloudFlare\'s edge servers will cache a resource before getting back to your server for a fresh copy.',
	) );

echo '</table>';
self::cloudflare_button_save( 'caching' );
Util_Ui::postbox_footer();
Util_Ui::postbox_header( __( 'CloudFlare: Content Processing', 'w3-total-cache' ), '', 'general' );
echo '<table class="form-table">';


self::cloudflare_selectbox( $settings, array(
		'key' => 'rocket_loader',
		'label' => __( 'Rocket Loader:', 'w3-total-cache' ),
		'values' => array(
			'' => '',
			'off' => 'Off',
			'on' => 'On (automatically run on the JavaScript resources on your site)',
			'manual' => 'Manual (run when attribute present only)'
		),
		'description' => 'Rocket Loader is a general-purpose asynchronous JavaScript loader coupled with a lightweight virtual browser which can safely run any JavaScript code after window.onload.'
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'minify_js',
		'label' => 'Minify <acronym title="JavaScript">JS</acronym>:',
		'description' => 'Minify JavaScript files.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'minify_css',
		'label' => 'Minify <acronym title="Cascading Style Sheet">CSS</acronym>:',
		'description' => 'Minify CSS files.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'minify_html',
		'label' => 'Minify <acronym title="HyperText Markup Language">HTML</acronym>:',
		'description' => 'Minify <acronym title="HyperText Markup Language">HTML</acronym> content.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'server_side_exclude',
		'label' => 'Server side exclude:',
		'description' => 'If there is sensitive content on your website that you want visible to real visitors, but that you want to hide from suspicious visitors, all you have to do is wrap the content with CloudFlare SSE tags.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'email_obfuscation',
		'label' => 'Email obfuscation:',
		'description' => 'Encrypt email adresses on your web page from bots, while keeping them visible to humans. ',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'response_buffering',
		'label' => 'Response buffering"',
		'description' => 'CloudFlare may buffer the whole payload to deliver it at once to the client versus allowing it to be delivered in chunks.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'prefetch_preload',
		'label' => 'Prefetch preload:',
		'description' => 'CloudFlare will prefetch any URLs that are included in the response headers.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'mobile_redirect',
		'label' => 'Mobile redirect:',
		'description' => 'Automatically redirect visitors on mobile devices to a mobile-optimized subdomain',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'origin_error_page_pass_thru',
		'label' => 'Enable error pages:',
		'description' => 'CloudFlare will proxy customer error pages on any 502,504 errors on origin server instead of showing a default CloudFlare error page. This does not apply to 522 errors and is limited to Enterprise Zones.',
	) );


echo '</table>';
self::cloudflare_button_save( 'content_processing' );
Util_Ui::postbox_footer();
Util_Ui::postbox_header( __( 'CloudFlare: Image Processing', 'w3-total-cache' ), '', 'general' );
echo '<table class="form-table">';


self::cloudflare_checkbox( $settings, array(
		'key' => 'hotlink_protection',
		'label' => 'Hotlink protection:',
		'description' => 'When enabled, the Hotlink Protection option ensures that other sites cannot suck up your bandwidth by building pages that use images hosted on your site.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'mirage',
		'label' => 'Mirage:',
		'description' => 'Automatically optimize image loading for website visitors on mobile devices',
	) );
self::cloudflare_selectbox( $settings, array(
		'key' => 'polish',
		'label' => __( 'Images polishing:', 'w3-total-cache' ),
		'values' => array(
			'' => '',
			'off' => 'Off',
			'lossless' => 'Lossless (Reduce the size of PNG, JPEG, and GIF files - no impact on visual quality)',
			'lossy' => 'Lossy (Further reduce the size of JPEG files for faster image loading)'
		),
		'description' => 'Strips metadata and compresses your images for faster page load times.'
	) );


echo '</table>';
self::cloudflare_button_save( 'image_processing' );
Util_Ui::postbox_footer();
Util_Ui::postbox_header( __( 'CloudFlare: Protection', 'w3-total-cache' ), '', 'general' );
echo '<table class="form-table">';


self::cloudflare_selectbox( $settings, array(
		'key' => 'security_level',
		'label' => __( 'Security level:', 'w3-total-cache' ),
		'values' => array(
			'' => '',
			'essentially_off' => 'Off',
			'low' => 'Low',
			'medium' => 'Medium',
			'high' => 'High',
			'under_attack' => 'Under Attack'
		),
		'description' => 'security profile for your website, which will automatically adjust each of the security settings.'
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'browser_check',
		'label' => 'Browser integrity check:',
		'description' => 'Browser Integrity Check is similar to Bad Behavior and looks for common HTTP headers abused most commonly by spammers and denies access to your page. It will also challenge visitors that do not have a user agent or a non standard user agent (also commonly used by abuse bots, crawlers or visitors).',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'always_online',
		'label' => 'Always online:',
		'description' => 'When enabled, Always Online will serve pages from our cache if your server is offline',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'waf',
		'label' => 'Web application firewall:',
		'description' => 'The Web Application Firewall (WAF) examines HTTP requests to your website. It inspects both GET and POST requests and applies rules to help filter out illegitimate traffic from legitimate website visitors.'
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'advanced_ddos',
		'label' => 'Advanced <acronym title="Distributed Denial of Service">DDoS</acronym> protection:',
		'description' => 'Advanced protection from Distributed Denial of Service (DDoS) attacks on your website.',
	) );
self::cloudflare_textbox( $settings, array(
		'key' => 'max_upload',
		'label' => 'Max upload:',
		'description' => 'Max size of file allowed for uploading',
	) );


echo '</table>';
self::cloudflare_button_save( 'protection' );
Util_Ui::postbox_footer();
Util_Ui::postbox_header( __( 'CloudFlare: <acronym title="Internet Protocol">IP</acronym>', 'w3-total-cache' ), '', 'general' );
echo '<table class="form-table">';


self::cloudflare_checkbox( $settings, array(
		'key' => 'ip_geolocation',
		'label' => '<acronym title="Internet Protocol">IP</acronym> geolocation:',
		'description' => 'Enable <acronym title="Internet Protocol">IP</acronym> Geolocation to have CloudFlare geolocate visitors to your website and pass the country code to you.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'ipv6',
		'label' => 'IPv6:',
		'description' => 'Enable IPv6.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'true_client_ip_header',
		'label' => 'True client IP:',
		'description' => 'Allows customer to continue to use True Client IP (Akamai feature) in the headers we send to the origin.',
	) );


echo '</table>';
self::cloudflare_button_save( 'ip' );
Util_Ui::postbox_footer();
Util_Ui::postbox_header( __( 'CloudFlare: <acronym title="Secure Sockets Layer">SSL</acronym>', 'w3-total-cache' ), '', 'general' );
echo '<table class="form-table">';


self::cloudflare_selectbox( $settings, array(
		'key' => 'ssl',
		'label' => '<acronym title="Secure Sockets Layer">SSL</acronym>:',
		'values' => array(
			'' => '',
			'off' => 'Off',
			'flexible' => 'Flexible (HTTPS to end-user only)',
			'full' => 'Full (https everywhere)',
			'full_strict' => 'Strict'
		),
		'description' => '<acronym title="Secure Sockets Layer">SSL</acronym> encrypts your visitor\'s connection and safeguards credit card numbers and other personal data to and from your website.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'security_header',
		'label' => 'Security header (<acronym title="Secure Sockets Layer">SSL</acronym>):',
		'description' => 'Enables or disables <acronym title="Secure Sockets Layer">SSL</acronym> header.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'tls_1_2_only',
		'label' => '<acronym title="Transport Layer Security">TLS</acronym> 1.2 only:',
		'description' => 'Enable Crypto <acronym title="Transport Layer Security">TLS</acronym> 1.2 feature for this zone and prevent use of previous versions.',
	) );
self::cloudflare_checkbox( $settings, array(
		'key' => 'tls_client_auth',
		'label' => '<acronym title="Transport Layer Security">TLS</acronym> client auth:',
		'description' => '<acronym title="Transport Layer Security">TLS</acronym> Client authentication requires CloudFlare to connect to your origin server using a client certificate',
	) );

echo '</table>';
self::cloudflare_button_save( 'ssl' );
Util_Ui::postbox_footer();
endif;
?>
    </div>
</form>
