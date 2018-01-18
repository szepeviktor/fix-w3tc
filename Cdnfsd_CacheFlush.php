<?php
namespace W3TC;

/**
 * CDN cache flusher
 */
class Cdnfsd_CacheFlush {
	/**
	 * Array of urls to flush
	 *
	 * @var array
	 */
	private $queued_urls = array();
	private $flush_all_requested = false;



	/**
	 * Purges everything from CDNs that supports it
	 */
	static public function w3tc_flush_all( $extras = array() ) {
		if ( isset( $extras['only'] ) && $extras['only'] != 'cdn' )
			return;

		$o = Dispatcher::component( 'Cdnfsd_CacheFlush' );

		$o->flush_all_requested = true;
		return true;
	}



	/**
	 * Purges cdn's post cache
	 *
	 * @param integer $post_id
	 * @return boolean
	 */
	static public function w3tc_flush_post( $post_id ) {
		if ( !$post_id ) {
			$post_id = Util_Environment::detect_post_id();
		}

		if ( !$post_id )
			return false;

		$config = Dispatcher::config();
		$full_urls = array();
		$post = null;
		$terms = array();

		$feeds = $config->get_array( 'pgcache.purge.feed.types' );
		$limit_post_pages = $config->get_integer( 'pgcache.purge.postpages_limit' );

		if ( $config->get_boolean( 'pgcache.purge.terms' ) || $config->get_boolean( 'varnish.pgcache.feed.terms' ) ) {
			$taxonomies = get_post_taxonomies( $post_id );
			$terms = wp_get_post_terms( $post_id, $taxonomies );
		}

		switch ( true ) {
		case $config->get_boolean( 'pgcache.purge.author' ):
		case $config->get_boolean( 'pgcache.purge.archive.daily' ):
		case $config->get_boolean( 'pgcache.purge.archive.monthly' ):
		case $config->get_boolean( 'pgcache.purge.archive.yearly' ):
		case $config->get_boolean( 'pgcache.purge.feed.author' ):
			$post = get_post( $post_id );
		}

		$front_page = get_option( 'show_on_front' );

		/**
		 * Home (Frontpage) URL
		 */
		if ( ( $config->get_boolean( 'pgcache.purge.home' ) && $front_page == 'posts' )||
			$config->get_boolean( 'pgcache.purge.front_page' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_frontpage_urls( $limit_post_pages ) );
		}

		/**
		 * Home (Post page) URL
		 */
		if ( $config->get_boolean( 'pgcache.purge.home' ) && $front_page != 'posts' ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_postpage_urls( $limit_post_pages ) );
		}

		/**
		 * Post URL
		 */
		if ( $config->get_boolean( 'pgcache.purge.post' ) ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_post_urls( $post_id ) );
		}

		/**
		 * Post comments URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.comments' ) && function_exists( 'get_comments_pagenum_link' ) ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_post_comments_urls( $post_id ) );
		}

		/**
		 * Post author URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.author' ) && $post ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_post_author_urls( $post->post_author, $limit_post_pages ) );
		}

		/**
		 * Post terms URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.terms' ) ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_post_terms_urls( $terms, $limit_post_pages ) );
		}

		/**
		 * Daily archive URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.archive.daily' ) && $post ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_daily_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Monthly archive URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.archive.monthly' ) && $post ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_monthly_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Yearly archive URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.archive.yearly' ) && $post ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_yearly_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Feed URLs
		 */
		if ( $config->get_boolean( 'pgcache.purge.feed.blog' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_feed_urls( $feeds ) );
		}

		if ( $config->get_boolean( 'pgcache.purge.feed.comments' ) ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_feed_comments_urls( $post_id, $feeds ) );
		}

		if ( $config->get_boolean( 'pgcache.purge.feed.author' ) && $post ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_feed_author_urls( $post->post_author, $feeds ) );
		}

		if ( $config->get_boolean( 'pgcache.purge.feed.terms' ) ) {
			$full_urls = array_merge( $full_urls, Util_PageUrls::get_feed_terms_urls( $terms, $feeds ) );
		}

		/**
		 * Purge selected pages
		 */
		if ( $config->get_array( 'pgcache.purge.pages' ) ) {
			$pages = $config->get_array( 'pgcache.purge.pages' );
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_pages_urls( $pages ) );
		}

		/**
		 * Queue flush
		 */
		if ( count( $full_urls ) ) {
			$o = Dispatcher::component( 'Cdnfsd_CacheFlush' );

			foreach ( $full_urls as $url )
				$o->queued_urls[$url] = '*';
		}

		return true;
	}

	/**
	 * Purge a single url
	 *
	 * @param unknown $url
	 */
	static public function w3tc_flush_url( $url ) {
		$o = Dispatcher::component( 'Cdnfsd_CacheFlush' );
		$o->queued_urls[$url] = '*';

		return true;
	}

	/**
	 * Clears global and repeated urls
	 */
	static public function w3tc_flush_execute_delayed_operations( $actions_made ) {
		$o = Dispatcher::component( 'Cdnfsd_CacheFlush' );

		if ( $o->flush_all_requested ) {
			$core = Dispatcher::component( 'Cdnfsd_Core' );

			try {
				$engine = $core->get_engine();
				
				if ( !is_null( $engine ) ) {
					$engine->flush_all();
					$actions_made[] = array( 'module' => 'cdn' );
				}
			} catch ( \Exception $ex ) {
				$actions_made[] = array(
					'module' => 'cdn',
					'error' => $ex->getMessage()
				);
			}

			$o->flush_all_requested = false;
			$o->queued_urls = array();
		} else {
			$count = count( $o->queued_urls );
			if ( $count > 0 ) {
				$urls = array_keys( $o->queued_urls );

				$core = Dispatcher::component( 'Cdnfsd_Core' );
				
				try {
					$engine = $core->get_engine();
					
					if ( !is_null( $engine ) ) {
						$engine->flush_urls( $urls );
						$actions_made[] = array( 'module' => 'cdn' );
					}
				} catch ( \Exception $ex ) {
					$actions_made[] = array(
						'module' => 'cdn',
						'error' => $ex->getMessage()
					);
				}

				$o->queued_urls = array();
			}
		}

		return $actions_made;
	}
}
