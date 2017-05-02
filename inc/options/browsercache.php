<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<?php include W3TC_INC_DIR . '/options/common/header.php'; ?>

<form action="admin.php?page=<?php echo $this->_page; ?>" method="post">
    <p>
        <?php echo sprintf( __( 'Browser caching is currently %s.', 'w3-total-cache' ), '<span class="w3tc-' . ( $browsercache_enabled ? 'enabled">' . __( 'enabled', 'w3-total-cache' ) : 'disabled">' . __( 'disabled', 'w3-total-cache' ) ) . '</span>' ); ?>
    </p>
    <p>
        <?php echo Util_Ui::nonce_field( 'w3tc' ); ?>

        	<?php echo sprintf(
	__( '%sUpdate media query string%s to make existing file modifications visible to visitors with a primed cache', 'w3-total-cache' ),
	'<input type="submit" name="w3tc_flush_browser_cache" value="',
	'" ' . disabled( !( $browsercache_enabled && $browsercache_update_media_qs ), true, false ) . ' class="button" />' );
?>
    </p>
</form>
<form action="admin.php?page=<?php echo $this->_page; ?>" method="post">
    <div class="metabox-holder">
        <?php Util_Ui::postbox_header( __( 'General', 'w3-total-cache' ), '', 'general' ); ?>
        <p><?php _e( 'Specify global browser cache policy.', 'w3-total-cache' ) ?></p>
        <table class="form-table">
            <?php if ( !Util_Environment::is_nginx() ): ?>
            <tr>
                <th colspan="2">
                    <label>
                    <input id="browsercache_last_modified" type="checkbox" name="expires"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                           value="1"<?php checked( $browsercache_last_modified, true ); ?> /> <?php _e( 'Set Last-Modified header', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Set the Last-Modified header to enable 304 Not Modified response.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="2">
                    <label>
                        <input id="browsercache_expires" type="checkbox" name="expires"
                            <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                            value="1"<?php checked( $browsercache_expires, true ); ?> /> <?php _e( 'Set expires header', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Set the expires header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_cache_control" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> name="cache_control" value="1"<?php checked( $browsercache_cache_control, true ); ?> /> <?php _e( 'Set cache control header', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Set pragma and cache-control headers to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_etag" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="etag" value="1"<?php checked( $browsercache_etag, true ); ?> /> <?php _e( 'Set entity tag (ETag)', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Set the ETag header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_w3tc" type="checkbox" name="w3tc"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="1" <?php checked( $browsercache_w3tc, true ); ?> /> <?php _e( 'Set W3 Total Cache header', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Set this header to assist in identifying optimized files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_compression" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="compression"<?php checked( $browsercache_compression, true ); ?> value="1" /> <?php _e( 'Enable <acronym title="Hypertext Transfer Protocol">HTTP</acronym> (gzip) compression', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Reduce the download time for text-based files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_replace" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="replace" value="1"<?php checked( $browsercache_replace, true ); ?> /> <?php _e( 'Prevent caching of objects after settings change', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Whenever settings are changed, a new query string will be generated and appended to objects allowing the new policy to be applied.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_querystring" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="querystring" value="1"<?php checked( $browsercache_querystring, true ); ?> /> <?php _e( 'Remove query strings from static resources', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Resources with a "?" in the URL are not cached by some proxy caching servers.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th><label for="browsercache_replace_exceptions"><?php Util_Ui::e_config_label( 'browsercache.replace.exceptions' ) ?></label></th>
                <td>
                    <textarea id="browsercache_replace_exceptions"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                              name="browsercache__replace__exceptions" cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'browsercache.replace.exceptions' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Do not add the prevent caching query string to the specified URIs. Supports regular expressions.', 'w3-total-cache' ); ?></span>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <label><input id="browsercache_nocookies" type="checkbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="nocookies" value="1"<?php checked( $browsercache_nocookies, true ); ?> /> <?php _e( "Don't set cookies for static files", 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Removes Set-Cookie header for responses.' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.no404wp', !Util_Rule::can_check_rules() ) ?> <?php Util_Ui::e_config_label( 'browsercache.no404wp' ) ?></label>
                    <br /><span class="description"><?php _e( 'Reduce server load by allowing the web server to handle 404 (not found) errors for static files (images etc).', 'w3-total-cache' ); ?></span>
                    <br /><span class="description"><?php _e( 'If enabled - you may get 404 File Not Found response for some files generated on-the-fly by WordPress plugins. You may add those file URIs to 404 error exception list below to avoid that.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th><label for="browsercache_no404wp_exceptions"><?php Util_Ui::e_config_label( 'browsercache.no404wp.exceptions' ) ?></label></th>
                <td>
                    <textarea id="browsercache_no404wp_exceptions"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__no404wp__exceptions" cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'browsercache.no404wp.exceptions' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Never process 404 (not found) events for the specified URIs.', 'w3-total-cache' ); ?></span>
                </td>
            </tr>
            <?php
Util_Ui::config_item( array(
		'key' => 'browsercache.rewrite',
		'disabled' => Util_Ui::sealing_disabled( 'browsercache.' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Rewrite <acronym title="Universal Resource Locator">URL</acronym> structure of objects', 'w3-total-cache' ),
		'description' => __( 'Generate unique <acronym title="Universal Resource Indicator">URI</acronym> for each file protected from caching by browser.', 'w3-total-cache' ),
		'style' => '2'
	) );
?>
        </table>

        <?php Util_Ui::button_config_save( 'browsercache_general' ); ?>
        <?php Util_Ui::postbox_footer(); ?>

        <?php Util_Ui::postbox_header( __( '<acronym title="Cascading Style Sheet">CSS</acronym> &amp; <acronym title="JavaScript">JS</acronym>', 'w3-total-cache' ), '', 'css_js' ); ?>
        <p><?php _e( 'Specify browser cache policy for Cascading Style Sheets and JavaScript files.', 'w3-total-cache' ); ?></p>

        <table class="form-table">
            <?php if ( !Util_Environment::is_nginx() ): ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.last_modified' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.last_modified' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the Last-Modified header to enable 304 Not Modified response.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.expires' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.expires' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the expires header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_cssjs_lifetime"><?php Util_Ui::e_config_label( 'browsercache.cssjs.lifetime' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_cssjs_lifetime" type="text"
                       <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                       name="browsercache__cssjs__lifetime" value="<?php echo esc_attr( $this->_config->get_integer( 'browsercache.cssjs.lifetime' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.cache.control' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.cache.control' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set pragma and cache-control headers to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_cssjs_cache_policy"><?php Util_Ui::e_config_label( 'browsercache.cssjs.cache.policy' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_cssjs_cache_policy"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__cssjs__cache__policy">
                        <?php $value = $this->_config->get_string( 'browsercache.cssjs.cache.policy' ); ?>
                        <option value="cache"<?php selected( $value, 'cache' ); ?>>cache ("public")</option>
                        <option value="cache_public_maxage"<?php selected( $value, 'cache_public_maxage' ); ?>><?php _e( 'cache with max-age ("public, max-age=EXPIRES_SECONDS")', 'w3-total-cache' ); ?></option>
                        <option value="cache_validation"<?php selected( $value, 'cache_validation' ); ?>><?php _e( 'cache with validation ("public, must-revalidate, proxy-revalidate"', 'w3-total-cache' ); ?></option>
                        <option value="cache_maxage"<?php selected( $value, 'cache_maxage' ); ?>><?php _e( 'cache with max-age and validation ("max-age=EXPIRES_SECONDS, public, must-revalidate, proxy-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="cache_noproxy"<?php selected( $value, 'cache_noproxy' ); ?>><?php _e( 'cache without proxy ("private, must-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="no_cache"<?php selected( $value, 'no_cache' ); ?>><?php _e( 'no-cache ("max-age=0, private, no-store, no-cache, must-revalidate"', 'w3-total-cache' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.etag' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.etag' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the ETag header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.w3tc' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.w3tc' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set this header to assist in identifying optimized files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.compression' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.compression' ) ?>  </label>
                    <br /><span class="description"><?php _e( 'Reduce the download time for text-based files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.replace' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.replace' ) ?></label>
                    <br /><span class="description"><?php _e( 'Whenever settings are changed, a new query string will be generated and appended to objects allowing the new policy to be applied.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.querystring' ) ?> <?php _e( 'Remove query strings from static resources', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Resources with a "?" in the URL are not cached by some proxy caching servers.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.cssjs.nocookies' ) ?> <?php Util_Ui::e_config_label( 'browsercache.cssjs.nocookies' ) ?></label>
                    <br /><span class="description"><?php _e( 'Removes Set-Cookie header for responses.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
        </table>

        <?php Util_Ui::button_config_save( 'browsercache_css_js' ); ?>
        <?php Util_Ui::postbox_footer(); ?>

        <?php Util_Ui::postbox_header( __( '<acronym title="Hypertext Markup Language">HTML</acronym> &amp; <acronym title="Extensible Markup Language">XML</acronym>', 'w3-total-cache' ), '', 'html_xml' ); ?>
        <p><?php _e( 'Specify browser cache policy for posts, pages, feeds and text-based files.', 'w3-total-cache' ); ?></p>

        <table class="form-table">
            <?php if ( !Util_Environment::is_nginx() ): ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.last_modified' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.last_modified' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the Last-Modified header to enable 304 Not Modified response.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.expires' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.expires' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the expires header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th style="width: 250px;">
                    <label for="browsercache_html_lifetime"><?php Util_Ui::e_config_label( 'browsercache.html.lifetime' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_html_lifetime" type="text"
                       name="browsercache__html__lifetime"
                       <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                       value="<?php echo esc_attr( $this->_config->get_integer( 'browsercache.html.lifetime' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.cache.control' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.cache.control' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set pragma and cache-control headers to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_html_cache_policy"><?php Util_Ui::e_config_label( 'browsercache.html.cache.policy' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_html_cache_policy" name="browsercache__html__cache__policy"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>>
                        <?php $value = $this->_config->get_string( 'browsercache.html.cache.policy' ); ?>
                        <option value="cache"<?php selected( $value, 'cache' ); ?>>cache ("public")</option>
                        <option value="cache_public_maxage"<?php selected( $value, 'cache_public_maxage' ); ?>><?php _e( 'cache with max-age ("public, max-age=EXPIRES_SECONDS")', 'w3-total-cache' ); ?></option>
                        <option value="cache_validation"<?php selected( $value, 'cache_validation' ); ?>><?php _e( 'cache with validation ("public, must-revalidate, proxy-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="cache_maxage"<?php selected( $value, 'cache_maxage' ); ?>><?php _e( 'cache with max-age and validation ("max-age=EXPIRES_SECONDS, public, must-revalidate, proxy-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="cache_noproxy"<?php selected( $value, 'cache_noproxy' ); ?>><?php _e( 'cache without proxy ("private, must-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="no_cache"<?php selected( $value, 'no_cache' ); ?>><?php _e( 'no-cache ("max-age=0, private, no-store, no-cache, must-revalidate")', 'w3-total-cache' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.etag' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.etag' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the ETag header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.w3tc' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.w3tc' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set this header to assist in identifying optimized files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.html.compression' ) ?> <?php Util_Ui::e_config_label( 'browsercache.html.compression' ) ?></label>
                    <br /><span class="description"><?php _e( 'Reduce the download time for text-based files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
        </table>

        <?php Util_Ui::button_config_save( 'browsercache_html_xml' ); ?>
        <?php Util_Ui::postbox_footer(); ?>

        <?php Util_Ui::postbox_header( __( 'Media &amp; Other Files', 'w3-total-cache' ), '', 'media' ); ?>
        <table class="form-table">
            <?php if ( !Util_Environment::is_nginx() ): ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.last_modified' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.last_modified' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the Last-Modified header to enable 304 Not Modified response.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <?php endif; ?>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.expires' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.expires' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the expires header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th style="width: 250px;">
                    <label for="browsercache_other_lifetime"><?php Util_Ui::e_config_label( 'browsercache.other.lifetime' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_other_lifetime" type="text"
                       <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                       name="browsercache__other__lifetime" value="<?php echo esc_attr( $this->_config->get_integer( 'browsercache.other.lifetime' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.cache.control' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.cache.control' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set pragma and cache-control headers to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_other_cache_policy"><?php Util_Ui::e_config_label( 'browsercache.other.cache.policy' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_other_cache_policy"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__other__cache__policy">
                        <?php $value = $this->_config->get_string( 'browsercache.other.cache.policy' ); ?>
                        <option value="cache"<?php selected( $value, 'cache' ); ?>><?php _e( 'cache ("public")' ); ?></option>
                        <option value="cache_public_maxage"<?php selected( $value, 'cache_public_maxage' ); ?>><?php _e( 'cache with max-age ("public, max-age=EXPIRES_SECONDS")', 'w3-total-cache' ); ?></option>
                        <option value="cache_validation"<?php selected( $value, 'cache_validation' ); ?>><?php _e( 'cache with validation ("public, must-revalidate, proxy-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="cache_maxage"<?php selected( $value, 'cache_maxage' ); ?>><?php _e( 'cache with max-age and validation ("max-age=EXPIRES_SECONDS, public, must-revalidate, proxy-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="cache_noproxy"<?php selected( $value, 'cache_noproxy' ); ?>><?php _e( 'cache without proxy ("private, must-revalidate")', 'w3-total-cache' ); ?></option>
                        <option value="no_cache"<?php selected( $value, 'no_cache' ); ?>><?php _e( 'no-cache ("max-age=0, private, no-store, no-cache, must-revalidate")', 'w3-total-cache' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.etag' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.etag' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set the ETag header to encourage browser caching of files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.w3tc' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.w3tc' ) ?></label>
                    <br /><span class="description"><?php _e( 'Set this header to assist in identifying optimized files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.compression' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.compression' ) ?>
                    <br /><span class="description"><?php _e( 'Reduce the download time for text-based files.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.replace' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.replace' ) ?></label>
                    <br /><span class="description"><?php _e( 'Whenever settings are changed, a new query string will be generated and appended to objects allowing the new policy to be applied.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.querystring' ) ?> <?php _e( 'Remove query strings from static resources', 'w3-total-cache' ); ?></label>
                    <br /><span class="description"><?php _e( 'Resources with a "?" in the URL are not cached by some proxy caching servers.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.other.nocookies' ) ?> <?php Util_Ui::e_config_label( 'browsercache.other.nocookies' ) ?></label>
                    <br /><span class="description"><?php _e( 'Removes Set-Cookie header for responses.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
        </table>

        <?php Util_Ui::button_config_save( 'browsercache_media' ); ?>
        <?php Util_Ui::postbox_footer(); ?>
        
        <?php Util_Ui::postbox_header( __( 'Security Headers', 'w3-total-cache' ), '', 'security' ); ?>
        <p><?php _e( 'HTTP security headers provide another layer of protection for your website by helping to mitigate attacks and security vulnerabilities.', 'w3-total-cache' ); ?></p>
        <table class="form-table">
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.session.use_only_cookies' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.session.use_only_cookies' ) ?></label>
                    <br /><span class="description"><?php _e( 'This setting prevents attacks that are caused by passing session IDs in URLs.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.session.cookie_httponly' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.session.cookie_httponly' ) ?></label>
                    <br /><span class="description"><?php _e( 'This tells the user\'s browser not to make the session cookie accessible to client side scripting such as JavaScript. This makes it harder for an attacker to hijack the session ID and masquerade as the effected user.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.session.cookie_secure' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.session.cookie_secure' ) ?></label>
                    <br /><span class="description"><?php _e( 'This will prevent the user\'s session ID from being transmitted in plain text, making it much harder to hijack the user\'s session.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.hsts' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.hsts' ) ?></label>
                    <br /><span class="description"><?php _e( 'HTTP Strict-Transport-Security (HSTS) enforces secure (HTTP over <acronym title="Secure Sockets Layer">SSL</acronym>/TLS) connections to the server. This can help mitigate adverse effects caused by bugs and session leaks through cookies and links. It also helps defend against man-in-the-middle attacks.  If there are SSL negotiation warnings then users will not be permitted to ignore them.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_hsts_directive"><?php Util_Ui::e_config_label( 'browsercache.security.hsts.directive' ) ?></label>
                </th>			 
                <td>
                    <select id="browsercache_security_hsts_directive"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__hsts__directive">
                        <?php $value = $this->_config->get_string( 'browsercache.security.hsts.directive' ); ?>				
                        <option value="maxage"<?php selected( $value, 'maxage' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS', 'w3-total-cache' ); ?></option>
                        <option value="maxagepre"<?php selected( $value, 'maxagepre' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS; preload', 'w3-total-cache' ); ?></option>
                        <option value="maxageinc"<?php selected( $value, 'maxageinc' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS; includeSubDomains', 'w3-total-cache' ); ?></option>
                        <option value="maxageincpre"<?php selected( $value, 'maxageincpre' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS; includeSubDomains; preload', 'w3-total-cache' ); ?></option>
                    </select>
                    <div id="browsercache_security_hsts_directive_description"></div>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.xfo' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.xfo' ) ?></label>
                    <br /><span class="description"><?php _e( 'This tells the browser if it is permitted to render a page within a frame-like tag (i.e., &lt;frame&gt;, &lt;iframe&gt; or &lt;object&gt;). This is useful for preventing clickjacking attacks.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_xfo_directive"><?php Util_Ui::e_config_label( 'browsercache.security.xfo.directive' ) ?></label>
                </th>			 
                <td>
                    <select id="browsercache_security_xfo_directive"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__xfo__directive">
                        <?php $value = $this->_config->get_string( 'browsercache.security.xfo.directive' ); ?>
                        <option value="same"<?php selected( $value, 'same' ); ?>><?php _e( 'SameOrigin', 'w3-total-cache' ); ?></option>
                        <option value="deny"<?php selected( $value, 'deny' ); ?>><?php _e( 'Deny', 'w3-total-cache' ); ?></option>
                        <option value="allow"<?php selected( $value, 'allow' ); ?>><?php _e( 'Allow-From', 'w3-total-cache' ); ?></option>
                    </select>
                    <input id="browsercache_security_xfo_allow" type="text" name="browsercache__security__xfo__allow"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.xfo.allow' ) ); ?>" size="50" placeholder="Enter URL" />
                    <div id="browsercache_security_xfo_directive_description"></div>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.xss' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.xss' ) ?></label>
                    <br /><span class="description"><?php _e( 'This header enables the Cross-Site Scripting (XSS) filter. It helps to stop malicious scripts from being injected into your website. Although this is already built into and enabled by default in most browsers today it is made available here to enforce its reactivation if it was disabled within the user\'s browser.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_xss_directive"><?php Util_Ui::e_config_label( 'browsercache.security.xss.directive' ) ?></label>
                </th>			 
                <td>
                    <select id="browsercache_security_xss_directive"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__xss__directive">
                        <?php $value = $this->_config->get_string( 'browsercache.security.xss.directive' ); ?>
                        <option value="0"<?php selected( $value, '0' ); ?>><?php _e( '0', 'w3-total-cache' ); ?></option>
                        <option value="1"<?php selected( $value, '1' ); ?>><?php _e( '1', 'w3-total-cache' ); ?></option>
                        <option value="block"<?php selected( $value, 'block' ); ?>><?php _e( '1; mode=block', 'w3-total-cache' ); ?></option>
                    </select>
                    <div id="browsercache_security_xss_directive_description"></div>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.xcto' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.xcto' ) ?></label>
                    <br /><span class="description"><?php _e( 'This instructs the browser to not MIME-sniff a response outside its declared content-type. It helps to reduce drive-by download attacks and stops sites from serving malevolent content that could masquerade as an executable or dynamic HTML file.', 'w3-total-cache' ); ?></span>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.pkp' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.pkp' ) ?></label>
                    <br /><span class="description"><?php _e( 'HTTP Public Key Pinning (HPKP) is a security feature for HTTPS websites that can prevent fraudulently issued certificates from being used to impersonate existing secure websites.' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_pkp_pin"><?php Util_Ui::e_config_label( 'browsercache.security.pkp.pin' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_pkp_pin" type="text" name="browsercache__security__pkp__pin"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.pkp.pin' ) ); ?>" size="50" placeholder="Enter the Base64-Encode of the SHA256 Hash" />
                    <div><i><?php _e( 'This field is <b>required</b> and represents a <acronym title="Subject Public Key Information">SPKI</acronym> fingerprint. This pin is any public key within your current certificate chain.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_pkp_pin_backup"><?php Util_Ui::e_config_label( 'browsercache.security.pkp.pin.backup' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_pkp_pin_backup" type="text" name="browsercache__security__pkp__pin__backup"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.pkp.pin.backup' ) ); ?>" size="50" placeholder="Enter the Base64-Encode of the SHA256 Hash" />
                        <div><i><?php _e( 'This field is <b>also required</b> and represents your backup <acronym title="Subject Public Key Information">SPKI</acronym> fingerprint. This pin is any public key not in your current certificate chain and serves as a backup in case your certificate expires or has to be revoked.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_pkp_extra"><?php Util_Ui::e_config_label( 'browsercache.security.pkp.extra' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_security_pkp_extra"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__pkp__extra">
                        <?php $value = $this->_config->get_string( 'browsercache.security.pkp.extra' ); ?>
                        <option value="maxage"<?php selected( $value, 'maxage' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS', 'w3-total-cache' ); ?></option>
                        <option value="maxageinc"<?php selected( $value, 'maxageinc' ); ?>><?php _e( 'max-age=EXPIRES_SECONDS; includeSubDomains', 'w3-total-cache' ); ?></option>
                    </select>
                    <div id="browsercache_security_pkp_extra_description"></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_pkp_report_url"><?php Util_Ui::e_config_label( 'browsercache.security.pkp.report.url' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_pkp_report_url" type="text" name="browsercache__security__pkp__report__url"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.pkp.report.url' ) ); ?>" size="50" placeholder="Enter URL" />
                    <div><i><?php _e( 'This optional field can be used to specify a URL that clients will send reports to if pin validation failures occur. The report is sent as a POST request with a JSON body.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_pkp_report_only"><?php Util_Ui::e_config_label( 'browsercache.security.pkp.report.only' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_security_pkp_report_only"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__pkp__report__only">
                        <?php $value = $this->_config->get_string( 'browsercache.security.pkp.report.only' ); ?>
                        <option value="0"<?php selected( $value, '0' ); ?>><?php _e( 'No = Enforce HPKP', 'w3-total-cache' ); ?></option>
                        <option value="1"<?php selected( $value, '1' ); ?>><?php _e( 'Yes = Don\'t Enforce HPKP', 'w3-total-cache' ); ?></option>
                    </select>
                    <div id="browsercache_security_pkp_report_only_description"></div>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.referrer.policy' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.referrer.policy' ) ?></label>
                    <br /><span class="description"><?php _e( 'This header restricts the values of the referer header in outbound links.' ); ?></span>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_referrer_policy_directive"><?php Util_Ui::e_config_label( 'browsercache.security.referrer.policy.directive' ) ?></label>
                </th>
                <td>
                    <select id="browsercache_security_referrer_policy_directive"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?>
                        name="browsercache__security__referrer__policy__directive">
                        <?php $value = $this->_config->get_string( 'browsercache.security.referrer.policy.directive' ); ?>
                        <option value="0"<?php selected( $value, '0' ); ?>><?php _e( '""', 'w3-total-cache' ); ?></option>
                        <option value="no-referrer"<?php selected( $value, 'no-referrer' ); ?>><?php _e( 'no-referrer', 'w3-total-cache' ); ?></option>
                        <option value="no-referrer-when-downgrade"<?php selected( $value, 'no-referrer-when-downgrade' ); ?>><?php _e( 'no-referrer-when-downgrade', 'w3-total-cache' ); ?></option>
                        <option value="same-origin"<?php selected( $value, 'same-origin' ); ?>><?php _e( 'same-origin', 'w3-total-cache' ); ?></option>
                        <option value="origin"<?php selected( $value, 'origin' ); ?>><?php _e( 'origin', 'w3-total-cache' ); ?></option>
                        <option value="strict-origin"<?php selected( $value, 'strict-origin' ); ?>><?php _e( 'strict-origin', 'w3-total-cache' ); ?></option>
                        <option value="origin-when-cross-origin"<?php selected( $value, 'origin-when-cross-origin' ); ?>><?php _e( 'origin-when-cross-origin', 'w3-total-cache' ); ?></option>
                        <option value="unsafe-url"<?php selected( $value, 'unsafe-url' ); ?>><?php _e( 'unsafe-url', 'w3-total-cache' ); ?></option>
                    </select>
                    <div id="browsercache_security_referrer_policy_directive_description"></div>
                </td>
            </tr>
            <tr>
                <th colspan="2">
                    <?php $this->checkbox( 'browsercache.security.csp' ) ?> <?php Util_Ui::e_config_label( 'browsercache.security.csp' ) ?></label>
                    <br /><span class="description"><?php _e( 'The Content Security Policy (CSP) header reduces the risk of <acronym title="Cross-Site Scripting">XSS</acronym> attacks by allowing you to define where resources can be retrieved from, preventing browsers from loading data from any other locations. This makes it harder for an attacker to inject malicious code into your site.' ); ?></span>
                    <p><a onclick="w3tc_csp_reference()" href="javascript:void(0);">Quick Reference Chart</a></p>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_base"><?php Util_Ui::e_config_label( 'browsercache.security.csp.base' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_base" type="text" name="browsercache__security__csp__base"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.base' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Restricts the URLs which can be used in a document\'s &lt;base&gt; element.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_connect"><?php Util_Ui::e_config_label( 'browsercache.security.csp.connect' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_connect" type="text" name="browsercache__security__csp__connect"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.connect' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Limits the origins to which you can connect via XMLHttpRequest, WebSockets, and EventSource.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_font"><?php Util_Ui::e_config_label( 'browsercache.security.csp.font' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_font" type="text" name="browsercache__security__csp__font"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.font' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Specifies the origins that can serve web fonts.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_frame"><?php Util_Ui::e_config_label( 'browsercache.security.csp.frame' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_frame" type="text" name="browsercache__security__csp__frame"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.frame' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Restricts from where the protected resource can embed frames.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_img"><?php Util_Ui::e_config_label( 'browsercache.security.csp.img' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_img" type="text" name="browsercache__security__csp__img"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.img' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Specifies valid sources for images and favicons.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_media"><?php Util_Ui::e_config_label( 'browsercache.security.csp.media' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_media" type="text" name="browsercache__security__csp__media"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.media' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Specifies valid sources for loading media using the &lt;audio&gt; and &lt;video&gt; elements.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_object"><?php Util_Ui::e_config_label( 'browsercache.security.csp.object' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_object" type="text" name="browsercache__security__csp__object"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.object' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Allows control over the &lt;object&gt;, &lt;embed&gt;, and &lt;applet&gt; elements used by Flash and other plugins.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_script"><?php Util_Ui::e_config_label( 'browsercache.security.csp.script' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_script" type="text" name="browsercache__security__csp__script"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.script' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Specifies valid sources for JavaScript.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_style"><?php Util_Ui::e_config_label( 'browsercache.security.csp.style' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_style" type="text" name="browsercache__security__csp__style"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.style' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Specifies valid sources for CSS stylesheets.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_form"><?php Util_Ui::e_config_label( 'browsercache.security.csp.form' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_form" type="text" name="browsercache__security__csp__form"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.form' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Restricts the URLs which can be used as the target of form submissions from a given context.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_frame_ancestors"><?php Util_Ui::e_config_label( 'browsercache.security.csp.frame.ancestors' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_frame_ancestors" type="text" name="browsercache__security__csp__frame__ancestors"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.frame' ) ); ?>" size="50" placeholder="Example: 'none'" />
                    <div><i><?php _e( 'Specifies valid parents that may embed a page using &lt;frame&gt;, &lt;iframe&gt;, &lt;object&gt;, &lt;embed&gt;, or &lt;applet&gt;.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_plugin"><?php Util_Ui::e_config_label( 'browsercache.security.csp.plugin' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_plugin" type="text" name="browsercache__security__csp__plugin"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.plugin' ) ); ?>" size="50" placeholder="Example: application/x-shockwave-flash" />
                    <div><i><?php _e( 'Restricts the set of plugins that can be embedded into a document by limiting the types of resources which can be loaded.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_sandbox"><?php Util_Ui::e_config_label( 'browsercache.security.csp.sandbox' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_sandbox" type="text" name="browsercache__security__csp__sandbox"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.sandbox' ) ); ?>" size="50" placeholder="Example: allow-popups" />
                    <div><i><?php _e( 'This directive operates similarly to the &lt;iframe&gt; sandbox attribute by applying restrictions to a page\'s actions, including preventing popups, preventing the execution of plugins and scripts, and enforcing a same-origin policy.' ); ?></i></div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="browsercache_security_csp_default"><?php Util_Ui::e_config_label( 'browsercache.security.csp.default' ) ?></label>
                </th>
                <td>
                    <input id="browsercache_security_csp_default" type="text" name="browsercache__security__csp__default"
                        <?php Util_Ui::sealing_disabled( 'browsercache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'browsercache.security.csp.default' ) ); ?>" size="50" placeholder="Example: 'self' 'unsafe-inline' *.domain.com" />
                    <div><i><?php _e( 'Defines the defaults for directives you leave unspecified. Generally, this applies to any directive that ends with -src.' ); ?></i></div>
                </td>
            </tr>
        </table>

        <?php Util_Ui::button_config_save( 'browsercache_security' ); ?>
        <?php Util_Ui::postbox_footer(); ?>
    </div>
</form>

<?php include W3TC_INC_DIR . '/options/common/footer.php'; ?>
