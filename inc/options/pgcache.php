<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<?php include W3TC_INC_DIR . '/options/common/header.php'; ?>

<form action="admin.php?page=<?php echo $this->_page; ?>" method="post">
	<p>
		<?php echo
sprintf( __( 'Page caching via %1$s is currently %2$s', 'w3-total-cache' ),
	'<strong>'.Cache::engine_name( $this->_config->get_string( 'pgcache.engine' ) ).'</strong>',
	'<span class="w3tc-'.( $pgcache_enabled ? 'enabled">' . __( 'enabled', 'w3-total-cache' ) : 'disabled">' . __( 'disabled', 'w3-total-cache' ) ) . '</span>.'
);
?>
	</p>
	<p>
		<?php
echo sprintf( __( 'To rebuild the page cache use the %s operation', 'w3-total-cache' ),
	Util_Ui::nonce_field( 'w3tc' ) . '<input type="submit" name="w3tc_flush_pgcache" value="empty cache"' . disabled( $pgcache_enabled, false, false ) . ' class="button" />'
);
?>
	</p>
</form>

<form action="admin.php?page=<?php echo $this->_page; ?>" method="post">
	<div class="metabox-holder">
		<?php Util_Ui::postbox_header( __( 'General', 'w3-total-cache' ), '', 'general' ); ?>
		<table class="form-table">
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.cache.home' ); ?> <?php Util_Ui::e_config_label( 'pgcache.cache.home' ) ?></label><br />
					<span class="description"><?php _e( 'For many blogs this is your most visited page, it is recommended that you cache it.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<?php if ( get_option( 'show_on_front' ) != 'posts' ): ?>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.reject.front_page' ); ?> <?php Util_Ui::e_config_label( 'pgcache.reject.front_page' ) ?></label><br />
					<span class="description"><?php _e( 'By default the front page is cached when using static front page in reading settings.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<?php endif; ?>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.cache.feed' ) ?> <?php Util_Ui::e_config_label( 'pgcache.cache.feed' ) ?></label><br />
					<span class="description"><?php _e( 'Even if using a feed proxy service (like <a href="http://en.wikipedia.org/wiki/FeedBurner" target="_blank">FeedBurner</a>), enabling this option is still recommended.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.cache.ssl' ) ?> <?php Util_Ui::e_config_label( 'pgcache.cache.ssl' ) ?></label><br />
					<span class="description"><?php _e( 'Cache <acronym title="Secure Socket Layer">SSL</acronym> requests (uniquely) for improved performance.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.cache.query',
	( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ),
	'', true, ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ? 0 : null ) ) ?> <?php Util_Ui::e_config_label( 'pgcache.cache.query', 'settings' ) ?></label><br />
					<span class="description"><?php _e( 'Search result (and similar) pages will be cached if enabled.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.cache.404' ) ?> <?php Util_Ui::e_config_label( 'pgcache.cache.404' ); ?></label><br />
					<span class="description"><?php _e( 'Reduce server load by caching 404 pages. If the disk enhanced method of disk caching is used, 404 pages will be returned with a 200 response code. Use at your own risk.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.reject.logged' ) ?> <?php Util_Ui::e_config_label( 'pgcache.reject.logged' ) ?></label><br />
					<span class="description"><?php _e( 'Unauthenticated users may view a cached version of the last authenticated user\'s view of a given page. Disabling this option is not recommended.', 'w3-total-cache' ); ?></span>
				</th>
			</tr>
			<tr>
				<th>
					<?php $this->checkbox( 'pgcache.reject.logged_roles' ) ?> <?php Util_Ui::e_config_label( 'pgcache.reject.logged_roles' ) ?></label><br />
					<span class="description"><?php _e( 'Select user roles that should not receive cached pages:', 'w3-total-cache' ); ?></span>

					<div id="pgcache_reject_roles">
						<?php $saved_roles = $this->_config->get_array( 'pgcache.reject.roles' ); ?>
						<input type="hidden" name="pgcache__reject__roles" value="" /><br />
						<?php foreach ( get_editable_roles() as $role_name => $role_data ) : ?>
							<input type="checkbox" name="pgcache__reject__roles[]" value="<?php echo $role_name ?>" <?php checked( in_array( $role_name, $saved_roles ) ) ?> id="role_<?php echo $role_name ?>" />
							<label for="role_<?php echo $role_name ?>"><?php echo $role_data['name'] ?></label>
						<?php endforeach; ?>
					</div>
				</th>
			</tr>
		</table>

		<?php echo Util_Ui::button_config_save( 'pagecache_general' ); ?>
		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( __( 'Aliases', 'w3-total-cache' ), '', 'mirrors' ); ?>
		<table class="form-table">
			<?php
Util_Ui::config_item( array(
		'key' => 'pgcache.mirrors.enabled',
		'control' => 'checkbox',
		'label' => __( 'Cache alias hostnames:', 'w3-total-cache' ),
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'enabled' => !Util_Environment::is_wpmu_subdomain(),
		'description' => __( 'If the same WordPress content is accessed from different domains',
			'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => 'pgcache.mirrors.home_urls',
		'control' => 'textarea',
		'label' => __( 'Additional home <acronym title="Uniform Resource Locator">URL</acronym>s:', 'w3-total-cache' ),
		'enabled' => !Util_Environment::is_wpmu_subdomain(),
		'description' => __( 'Specify full home <acronym title="Uniform Resource Locator">URL</acronym>s of your mirrors so that plugin will flush it\'s cache when content is changed. For example:<br /> http://my-site.com<br />http://www.my-site.com<br />https://my-site.com',
			'w3-total-cache' )
	) );
?>
		</table>
		<?php echo Util_Ui::button_config_save( 'pagecache_aliases' ); ?>
		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( __( 'Cache Preload', 'w3-total-cache' ), '', 'cache_preload' ); ?>
		<table class="form-table">
			<tr>
				<th colspan="2">
					<?php $this->checkbox( 'pgcache.prime.enabled' ) ?> <?php Util_Ui::e_config_label( 'pgcache.prime.enabled' ) ?></label><br />
				</th>
			</tr>
			<tr>
				<th><label for="pgcache_prime_interval"><?php Util_Ui::e_config_label( 'pgcache.prime.interval' ) ?></label></th>
				<td>
					<input id="pgcache_prime_interval" type="text" name="pgcache__prime__interval"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.prime.interval' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?><br />
					<span class="description"><?php _e( 'The number of seconds to wait before creating another set of cached pages.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_prime_limit"><?php Util_Ui::e_config_label( 'pgcache.prime.limit' ) ?></label></th>
				<td>
					<input id="pgcache_prime_limit" type="text" name="pgcache__prime__limit"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.prime.limit' ) ); ?>" size="8" /><br />
                    <span class="description"><?php _e( 'Limit the number of pages to create per batch. Fewer pages may be better for under-powered servers. Set to 0 for no limit.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_prime_sitemap"><?php Util_Ui::e_config_label( 'pgcache.prime.sitemap' ) ?></label></th>
				<td>
					<input id="pgcache_prime_sitemap" type="text" name="pgcache__prime__sitemap"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						value="<?php echo esc_attr( $this->_config->get_string( 'pgcache.prime.sitemap' ) ); ?>" size="100" /><br />
					<span class="description"><?php _e( 'A <a href="http://www.xml-sitemaps.com/validate-xml-sitemap.html" target="_blank">compliant</a> sitemap can be used to specify the pages to maintain in the primed cache. Pages will be cached according to the priorities specified in the <acronym title="Extensible Markup Language">XML</acronym> file.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th colspan="2">
					<?php $this->checkbox( 'pgcache.prime.post.enabled' ) ?> <?php Util_Ui::e_config_label( 'pgcache.prime.post.enabled' ) ?></label><br />                </th>
			</tr>
		</table>

		<?php echo Util_Ui::button_config_save( 'pagecache_cache_preload' ); ?>
		<?php Util_Ui::postbox_footer(); ?>

		<?php
$modules = array();
if ( $pgcache_enabled ) $modules[] = 'Page Cache';
if ( $varnish_enabled ) $modules [] = 'Varnish';
if ( $cdn_mirror_purge_enabled ) $modules[] = 'CDN';
Util_Ui::postbox_header( __( 'Purge Policy: ', 'w3-total-cache' ) . implode( ', ', $modules ), '', 'purge_policy' ); ?>
		<table class="form-table">
			<tr>
				<th colspan="2">
					<?php _e( 'Specify the pages and feeds to purge when posts are created, edited, or comments posted. The defaults are recommended because additional options may reduce server performance:', 'w3-total-cache' ) ?>

					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<th style="padding-left: 0;">
								<?php if ( get_option( 'show_on_front' ) != 'posts' ): ?>
								<?php $this->checkbox( 'pgcache.purge.front_page' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.front_page' ) ?></label><br />
								<?php endif; ?>
								<?php $this->checkbox( 'pgcache.purge.home' ) ?>  <?php Util_Ui::e_config_label( 'pgcache.purge.home' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.post' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.post' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.feed.blog' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.feed.blog' ) ?></label><br />
							</th>
							<th>
								<?php $this->checkbox( 'pgcache.purge.comments' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.comments' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.author' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.author' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.terms' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.terms' ) ?></label><br />
							</th>
							<th>
								<?php $this->checkbox( 'pgcache.purge.feed.comments' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.feed.comments' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.feed.author' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.feed.author' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.feed.terms' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.feed.terms' ) ?></label>
							</th>
							<th>
								<?php $this->checkbox( 'pgcache.purge.archive.daily' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.archive.daily' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.archive.monthly' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.archive.monthly' ) ?></label><br />
								<?php $this->checkbox( 'pgcache.purge.archive.yearly' ) ?> <?php Util_Ui::e_config_label( 'pgcache.purge.archive.yearly' ) ?></label><br />
							</th>
						</tr>
					</table>
				</th>
			</tr>
			<tr>
				<th colspan="2">
					<?php Util_Ui::e_config_label( 'pgcache.purge.feed.types' ) ?><br />
					<input type="hidden" name="pgcache__purge__feed__types" value="" />
					<?php foreach ( $feeds as $feed ): ?>
						<label>
							<input type="checkbox" name="pgcache__purge__feed__types[]"
								value="<?php echo $feed; ?>"
								<?php checked( in_array( $feed, $this->_config->get_array( 'pgcache.purge.feed.types' ) ), true ); ?>
								<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
								/>
						<?php echo $feed; ?>
						<?php if ( $feed == $default_feed ): ?>(default)<?php endif; ?></label><br />
					<?php endforeach; ?>
				</th>
			</tr>
			<tr>
				<th><label for="pgcache_purge_postpages_limit"><?php Util_Ui::e_config_label( 'pgcache.purge.postpages_limit' ) ?></label></th>
				<td>
					<input id="pgcache_purge_postpages_limit" name="pgcache__purge__postpages_limit" <?php Util_Ui::sealing_disabled( 'pgcache.' ) ?> type="text" value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.purge.postpages_limit' ) ); ?>" /><br />
					<span class="description"><?php _e( 'Specify number of pages that lists posts (archive etc) that should be purged on post updates etc, i.e example.com/ ... example.com/page/5. <br />0 means all pages that lists posts are purged, i.e example.com/page/2 ... .', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_purge_pages"><?php Util_Ui::e_config_label( 'pgcache.purge.pages' ) ?></label></th>
				<td>
					<textarea id="pgcache_purge_pages" name="pgcache__purge__pages"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
							  cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.purge.pages' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Specify additional pages to purge. Including parent page in path. Ex: parent/posts.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_purge_sitemap_regex"><?php Util_Ui::e_config_label( 'pgcache.purge.sitemap_regex' ) ?></label></th>
				<td>
					<input id="pgcache_purge_sitemap_regex" name="pgcache__purge__sitemap_regex" <?php Util_Ui::sealing_disabled( 'pgcache.' ) ?> value="<?php echo esc_attr( $this->_config->get_string( 'pgcache.purge.sitemap_regex' ) ) ?>" type="text" /><br />
					<span class="description"><?php _e( 'Specify a regular expression that matches your sitemaps.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
		</table>

		<?php echo Util_Ui::button_config_save( 'pagecache_purge_policy' ); ?>
		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( __( 'Advanced', 'w3-total-cache' ), '', 'advanced' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="pgcache_late_init"><?php _e( 'Late initialization:', 'w3-total-cache' ); ?></label></th>
				<td>
					<input type="hidden" name="pgcache__late_init" value="0" />
					<label><input id="pgcache_late_init" type="checkbox" name="pgcache__late_init" value="1"<?php checked( $this->_config->get_string( 'pgcache.engine' ) != 'file_generic' && $this->_config->get_boolean( 'pgcache.late_init' ) ); ?> <?php disabled( $this->_config->get_string( 'pgcache.engine' ), 'file_generic' ) ?> /> <?php _e( 'Enable', 'w3-total-cache' ); ?></label>
					<br /><span class="description"><?php _e( 'Enables support for WordPress functionality in fragment caching for the page caching engine. Use of this feature may increase response times.', 'w3-total-cache' )?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_late_caching"><?php _e( 'Late caching:', 'w3-total-cache' ); ?></label></th>
				<td>
					<input type="hidden" name="pgcache__late_caching" value="0" />
					<label><input id="pgcache_late_caching" type="checkbox" name="pgcache__late_caching" value="1"<?php checked( $this->_config->get_string( 'pgcache.engine' ) != 'file_generic' && $this->_config->get_boolean( 'pgcache.late_caching' ) ); ?> <?php disabled( $this->_config->get_string( 'pgcache.engine' ), 'file_generic' ) ?> /> <?php _e( 'Enable', 'w3-total-cache' ); ?></label>
					<br /><span class="description"><?php _e( 'Overwrites key of page caching via custom filters by postponing entry extraction during the init action.', 'w3-total-cache' )?></span>
				</td>
			</tr>
			<?php
if ( $this->_config->get_string( 'pgcache.engine' ) == 'memcached' ) {
	$module = 'pgcache';
	include W3TC_INC_DIR . '/options/parts/memcached.php';
} elseif ( $this->_config->get_string( 'pgcache.engine' ) == 'redis' ) {
	$module = 'pgcache';
	include W3TC_INC_DIR . '/options/parts/redis.php';
}
?>
			<?php if ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ): ?>
			<tr>
				<th><label><?php _e( 'Compatibility mode:', 'w3-total-cache' ); ?></label></th>
				<td>
					<?php $this->checkbox( 'pgcache.compatibility' ) ?> <?php Util_Ui::e_config_label( 'pgcache.compatibility' ) ?></label><br />
					<span class="description"><?php _e( 'Decreases performance by ~20% at scale in exchange for increasing interoperability with more hosting environments and WordPress idiosyncrasies. This option should be enabled for most sites.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<?php if ( !Util_Environment::is_nginx() ): ?>
				<tr>
					<th><label><?php _e( 'Charset:', 'w3-total-cache' )?></label></th>
					<td>
						<?php $this->checkbox( 'pgcache.remove_charset' ) ?> <?php Util_Ui::e_config_label( 'pgcache.remove_charset' ) ?></label><br />
						<span class="description"><?php _e( 'Resolve issues incorrect odd character encoding that may appear in cached pages.', 'w3-total-cache' )?></span>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label for="pgcache_reject_request_head"><?php _e( 'Reject HEAD requests:', 'w3-total-cache' ); ?></label></th>
				<td>
					<?php if ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ):?>
					<input id="pgcache_reject_request_head" type="checkbox" name="pgcache__reject__request_head" value="1" disabled="disabled" /> <?php Util_Ui::e_config_label( 'pgcache.reject.request_head' ) ?><br />
					<?php else: ?>
					<?php $this->checkbox( 'pgcache.reject.request_head', false, '', false ) ?><?php Util_Ui::e_config_label( 'pgcache.reject.request_head' ) ?><br />
					<?php endif; ?>
					<span class="description"><?php _e( 'If disabled, HEAD requests can often be cached resulting in "empty pages" being returned for subsequent requests for a <acronym title="Uniform Resource Locator">URL</acronym>.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ( $this->_config->get_string( 'pgcache.engine' ) != 'file' && $this->_config->get_string( 'pgcache.engine' ) != 'file_generic' ): ?>
			<tr>
				<th><label for="pgcache_lifetime"><?php Util_Ui::e_config_label( 'pgcache.lifetime' ) ?></label></th>
				<td>
					<input id="pgcache_lifetime" type="text" name="pgcache__lifetime"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.lifetime' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?>
					<br /><span class="description"><?php _e( 'Determines the natural expiration time of unchanged cache items. The higher the value, the larger the cache.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<th><label for="pgcache_file_gc"><?php Util_Ui::e_config_label( 'pgcache.file.gc' ) ?></label></th>
				<td>
					<input id="pgcache_file_gc" type="text" name="pgcache__file__gc"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.file.gc' ) ); ?>" size="8"<?php if ( $this->_config->get_string( 'pgcache.engine' ) != 'file' && $this->_config->get_string( 'pgcache.engine' ) != 'file_generic' ): ?> disabled="disabled"<?php endif; ?> /> <?php _e( 'seconds', 'w3-total-cache' ) ?>
					<br /><span class="description"><?php _e( 'If caching to disk, specify how frequently expired cache data is removed. For busy sites, a lower value is best.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_comment_cookie_ttl"><?php Util_Ui::e_config_label( 'pgcache.comment_cookie_ttl' ) ?></label></th>
				<td>
						<input id="pgcache_comment_cookie_ttl" type="text" name="pgcache__comment_cookie_ttl" value="<?php echo esc_attr( $this->_config->get_integer( 'pgcache.comment_cookie_ttl' ) ); ?>" size="8" /> <?php _e( 'seconds', 'w3-total-cache' ); ?>
						<br /><span class="description"><?php _e( 'Significantly reduce the default <acronym title="Time to Live">TTL</acronym> for comment cookies to reduce the number of authenticated user traffic. Enter -1 to revert to default <acronym title="Time to Live">TTL</acronym>.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_accept_qs"><?php Util_Ui::e_config_label( 'pgcache.accept.qs' ) ?></label></th>
				<td>
					<textarea id="pgcache_accept_qs" name="pgcache__accept__qs"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
							  cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.accept.qs' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Always cache <acronym title="Uniform Resource Locator">URL</acronym>s that use these query string name-value pairs. The value part is not required. But if used, separate name-value pairs with an equals sign (i.e., name=value). Each pair should be on their own line.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_reject_ua"><?php Util_Ui::e_config_label( 'pgcache.reject.ua' ) ?></label></th>
				<td>
					<textarea id="pgcache_reject_ua" name="pgcache__reject__ua"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.reject.ua' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Never send cache pages for these user agents.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_reject_cookie"><?php Util_Ui::e_config_label( 'pgcache.reject.cookie' ) ?></label></th>
				<td>
					<textarea id="pgcache_reject_cookie" name="pgcache__reject__cookie"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.reject.cookie' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Never cache pages that use the specified cookies.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_reject_uri"><?php Util_Ui::e_config_label( 'pgcache.reject.uri' ) ?></label></th>
				<td>
					<textarea id="pgcache_reject_uri" name="pgcache__reject__uri"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.reject.uri' ) ) ); ?></textarea><br />
					<span class="description">
						<?php
echo sprintf(
	__( 'Always ignore the specified pages / directories. Supports regular expressions (See <a href="%s">FAQ</a>)', 'w3-total-cache' ),           network_admin_url( 'admin.php?page=w3tc_faq#q82' )
); ?>
					</span>
				</td>
			</tr>
            <tr>
                <th><label for="pgcache_reject_categories"><?php Util_Ui::e_config_label( 'pgcache.reject.categories' ) ?></label></th>
                <td>
                    <textarea id="pgcache_reject_categories" name="pgcache__reject__categories" 
                        <?php Util_Ui::sealing_disabled( 'pgcache' ) ?>
                        cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array('pgcache.reject.categories' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Always ignore all pages filed under the specified category slugs.', 'w3-total-cache' ); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="pgcache_reject_tags"><?php Util_Ui::e_config_label( 'pgcache.reject.tags' ) ?></label></th>
                <td>
                    <textarea id="pgcache_reject_tags" name="pgcache__reject__tags" 
                        <?php Util_Ui::sealing_disabled( 'pgcache' ) ?>
                        cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.reject.tags' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Always ignore all pages filed under the specified tag slugs.', 'w3-total-cache' ); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="pgcache_reject_authors"><?php Util_Ui::e_config_label( 'pgcache.reject.authors' ) ?></label></th>
                <td>
                    <textarea id="pgcache_reject_authors" name="pgcache__reject__authors" 
                        <?php Util_Ui::sealing_disabled( 'pgcache' ) ?>
                        cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.reject.authors' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Always ignore all pages filed under the specified author usernames.', 'w3-total-cache' ); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="pgcache_reject_custom"><?php Util_Ui::e_config_label( 'pgcache.reject.custom' ) ?></label></th>
                <td>
                    <textarea id="pgcache_reject_custom" name="pgcache__reject__custom" 
                        <?php Util_Ui::sealing_disabled( 'pgcache' ) ?>
                        cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array('pgcache.reject.custom' ) ) ); ?></textarea><br />
                    <span class="description"><?php _e( 'Always ignore all pages filed under the specified custom fields. Separate name-value pairs with an equals sign (i.e., name=value).', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><label for="pgcache_accept_files"><?php Util_Ui::e_config_label( 'pgcache.accept.files' ) ?></label></th>
				<td>
					<textarea id="pgcache_accept_files" name="pgcache__accept__files"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.accept.files' ) ) ); ?></textarea><br />
					<span class="description"><?php echo sprintf( __( 'Cache the specified pages / directories even if listed in the "never cache the following pages" field. Supports regular expression (See <a href="%s">FAQ</a>)', 'w3-total-cache' ), network_admin_url( 'admin.php?page=w3tc_faq#q82' ) ); ?></span>
				</td>
			</tr>
			<?php if ( substr( $permalink_structure, -1 ) == '/' ): ?>
			<tr>
				<th><label for="pgcache_accept_uri"><?php Util_Ui::e_config_label( 'pgcache.accept.uri' ) ?></label></th>
				<td>
					<textarea id="pgcache_accept_uri" name="pgcache__accept__uri"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.accept.uri' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Cache the specified pages even if they don\'t have tailing slash.', 'w3-total-cache' ); ?></span>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<th><label for="pgcache_cache_headers"><?php Util_Ui::e_config_label( 'pgcache.cache.headers' ) ?></label></th>
				<td>
					<textarea id="pgcache_cache_headers" name="pgcache__cache__headers"
						<?php Util_Ui::sealing_disabled( 'pgcache.' ) ?>
						cols="40" rows="5"<?php if ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ): ?> disabled="disabled"<?php endif; ?>><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'pgcache.cache.headers' ) ) ); ?></textarea><br />
					<span class="description"><?php _e( 'Specify additional page headers to cache.', 'w3-total-cache' )?></span>
				</td>
			</tr>
			<?php if ( $this->_config->get_string( 'pgcache.engine' ) == 'file_generic' ): ?>
			<tr>
				<?php if( Util_Environment::is_nginx() ): ?>
				<th><label><?php Util_Ui::e_config_label('pgcache.cache.nginx_handle_xml') ?></label></th>
				<td>
					<?php $this->checkbox('pgcache.cache.nginx_handle_xml') ?> <?php Util_Ui::e_config_label('pgcache.cache.nginx_handle_xml') ?><br />
					<span class="description"><?php _e( 'Return correct Content-Type header for XML files (e.g., feeds and sitemaps). Slows down cache engine.', 'w3-total-cache' ); ?></span>
				</td>
				<?php else: ?>
				<th><label><?php Util_Ui::e_config_label('pgcache.cache.apache_handle_xml') ?></label></th>
				<td>
					<?php $this->checkbox('pgcache.cache.apache_handle_xml') ?> <?php Util_Ui::e_config_label('pgcache.cache.apache_handle_xml') ?></label><br />
					<span class="description"><?php _e( 'Return correct Content-Type header for XML files (e.g., feeds and sitemaps). Slows down cache engine.', 'w3-total-cache' ); ?></span>
				</td>
				<?php endif; ?>
			</tr>
			<?php endif; ?>
		</table>

		<?php Util_Ui::button_config_save( 'pagecache_advanced' ); ?>
		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( __( 'Note(s)', 'w3-total-cache' ), '', 'notes' ); ?>
		<table class="form-table">
			<tr>
				<th>
					<ul>
						<li><?php _e( 'Enable <acronym title="Hypertext Transfer Protocol">HTTP</acronym> compression in the "<acronym title="Hypertext Markup Language">HTML</acronym>" section on <a href="admin.php?page=w3tc_browsercache">Browser Cache</a> Settings tab.', 'w3-total-cache' ); ?></li>
						<li><?php _e( 'The <acronym title="Time to Live">TTL</acronym> of page cache files is set via the "Expires header lifetime" field in the "<acronym title="Hypertext Markup Language">HTML</acronym>" section on <a href="admin.php?page=w3tc_browsercache">Browser Cache</a> Settings tab.', 'w3-total-cache' ); ?></li>
					</ul>
				</th>
			</tr>
		</table>
		<?php Util_Ui::postbox_footer(); ?>
	</div>
</form>

<?php include W3TC_INC_DIR . '/options/common/footer.php'; ?>
