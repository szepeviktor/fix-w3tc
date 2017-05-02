<?php
namespace W3TC;

/**
 * W3 PgCache flushing
 */
class PgCache_Flush extends PgCache_ContentGrabber {
	/**
	 * Array of urls to flush
	 *
	 * @var array
	 */
	private $queued_urls = array();
	private $flush_operation_requested = false;

	/**
	 * PHP5 Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Flushes all caches
	 *
	 * @return boolean
	 */
	function flush() {
		$this->flush_operation_requested = true;
		return true;
	}

	/**
	 * Flushes post cache
	 *
	 * @param integer $post_id
	 * @return boolean
	 */
	function flush_post( $post_id = null ) {
		if ( !$post_id ) {
			$post_id = Util_Environment::detect_post_id();
		}

		if ( !$post_id )
			return false;

		$full_urls = array();
		$post = null;
		$terms = array();

		$feeds = $this->_config->get_array( 'pgcache.purge.feed.types' );
		$limit_post_pages = $this->_config->get_integer( 'pgcache.purge.postpages_limit' );

		if ( $this->_config->get_boolean( 'pgcache.purge.terms' ) ||
			$this->_config->get_boolean( 'pgcache.purge.feed.terms' ) ) {
			$taxonomies = get_post_taxonomies( $post_id );
			$terms = wp_get_post_terms( $post_id, $taxonomies );
			$terms = $this->_append_parent_terms( $terms, $terms );
		}

		$post = get_post( $post_id );
		$post_type = null;
		if( is_object($post) ){
			$post_type = in_array( $post->post_type, array(
					'post', 'page', 'attachment', 'revision' ) ) ? null : $post->post_type;
		}
		$front_page = get_option( 'show_on_front' );

		/**
		 * Home (Frontpage) URL
		 */
		if ( ( $this->_config->get_boolean( 'pgcache.purge.home' ) &&
				$front_page == 'posts' ) ||
			$this->_config->get_boolean( 'pgcache.purge.front_page' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_frontpage_urls( $limit_post_pages ) );
		}

		/**
		 * Home (Post page) URL
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.home' ) &&
			$front_page != 'posts' ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_postpage_urls( $limit_post_pages ) );
		}

		/**
		 * Post URL
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.post' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_post_urls( $post_id ) );
		}

		/**
		 * Post comments URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.comments' ) &&
			function_exists( 'get_comments_pagenum_link' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_post_comments_urls( $post_id ) );
		}

		/**
		 * Post author URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.author' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_post_author_urls( $post->post_author,
					$limit_post_pages ) );
		}

		/**
		 * Post terms URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.terms' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_post_terms_urls( $terms, $limit_post_pages ) );
		}

		/**
		 * Daily archive URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.archive.daily' ) && $post ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_daily_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Monthly archive URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.archive.monthly' ) && $post ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_monthly_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Yearly archive URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.archive.yearly' ) && $post ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_yearly_archive_urls( $post, $limit_post_pages ) );
		}

		/**
		 * Feed URLs
		 */
		if ( $this->_config->get_boolean( 'pgcache.purge.feed.blog' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_feed_urls( $feeds, $post_type ) );
		}

		if ( $this->_config->get_boolean( 'pgcache.purge.feed.comments' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_feed_comments_urls( $post_id, $feeds ) );
		}

		if ( $this->_config->get_boolean( 'pgcache.purge.feed.author' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_feed_author_urls( $post->post_author, $feeds ) );
		}

		if ( $this->_config->get_boolean( 'pgcache.purge.feed.terms' ) ) {
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_feed_terms_urls( $terms, $feeds ) );
		}

		/**
		 * Purge selected pages
		 */
		if ( $this->_config->get_array( 'pgcache.purge.pages' ) ) {
			$pages = $this->_config->get_array( 'pgcache.purge.pages' );
			$full_urls = array_merge( $full_urls,
				Util_PageUrls::get_pages_urls( $pages ) );
		}

		// add mirror urls
		$full_urls = Util_PageUrls::complement_with_mirror_urls( $full_urls );
		$full_urls = apply_filters( 'pgcache_flush_post_queued_urls',
			$full_urls );

		/**
		 * Queue flush
		 */
		if ( count( $full_urls ) ) {
			foreach ( $full_urls as $url )
				$this->queued_urls[$url] = '*';
		}

		return true;
	}

	/**
	 * Flush a single url
	 *
	 * @param unknown $url
	 * @param unknown $cache
	 * @param unknown $mobile_groups
	 * @param unknown $referrer_groups
	 * @param unknown $encryptions
	 * @param unknown $compressions
	 */
	function _flush_url( $url, $cache, $mobile_groups, $referrer_groups,
		$encryptions, $compressions ) {
		foreach ( $mobile_groups as $mobile_group ) {
			foreach ( $referrer_groups as $referrer_group ) {
				foreach ( $encryptions as $encryption ) {
					foreach ( $compressions as $compression ) {
						$page_keys = array();
						$page_keys[] = $this->_get_page_key( $mobile_group,
							$referrer_group, $encryption, $compression, false,
							$url );
						$page_keys = apply_filters(
							'w3tc_pagecache_flush_url_keys', $page_keys );

						foreach ( $page_keys as $page_key )
							$cache->delete( $page_key );
					}
				}
			}
		}
	}

	/**
	 * Flush a single url
	 *
	 * @param unknown $url
	 */
	function flush_url( $url ) {
		static $cache, $mobile_groups, $referrer_groups, $encryptions;
		static $compressions;

		if ( !isset( $cache ) )
			$cache = $this->_get_cache();
		if ( !isset( $mobile_groups ) )
			$mobile_groups  = $this->_get_mobile_groups();
		if ( !isset( $referrer_groups ) )
			$referrer_groups = $this->_get_referrer_groups();
		if ( !isset( $encryptions ) )
			$encryptions = $this->_get_encryptions();
		if ( !isset( $compressions ) )
			$compressions = $this->_get_compressions();
		$this->_flush_url( $url, $cache, $mobile_groups, $referrer_groups,
			$encryptions, $compressions );
	}

	/**
	 * Flushes global and repeated urls
	 *
	 * @return count of elements it has flushed
	 */
	function flush_post_cleanup() {
		if ( $this->flush_operation_requested ) {
			$cache = $this->_get_cache();
			$cache->flush();

			$count = 999;
			$this->flush_operation_requested = false;
			$this->queued_urls = array();
		} else {
			$count = count( $this->queued_urls );

			if ( $count > 0 ) {
				$cache = $this->_get_cache();
				$mobile_groups = $this->_get_mobile_groups();
				$referrer_groups = $this->_get_referrer_groups();
				$encryptions = $this->_get_encryptions();
				$compressions = $this->_get_compressions();

				foreach ( $this->queued_urls as $url => $flag ) {
					$this->_flush_url( $url, $cache, $mobile_groups,
						$referrer_groups, $encryptions, $compressions );
				}

				// Purge sitemaps if a sitemap option has a regex
				if ( $this->_config->get_string( 'pgcache.purge.sitemap_regex' ) ) {
					$cache = $this->_get_cache();
					$cache->flush( 'sitemaps' );
				}

				$this->queued_urls = array();
			}
		}

		return $count;
	}

	/**
	 * Returns array of mobile groups
	 *
	 * @return array
	 */
	function _get_mobile_groups() {
		$mobile_groups = array( '' );

		if ( $this->_mobile ) {
			$mobile_groups = array_merge( $mobile_groups, array_keys(
					$this->_mobile->get_groups() ) );
		}

		return $mobile_groups;
	}

	/**
	 * Returns array of referrer groups
	 *
	 * @return array
	 */
	function _get_referrer_groups() {
		$referrer_groups = array( '' );

		if ( $this->_referrer ) {
			$referrer_groups = array_merge( $referrer_groups, array_keys(
					$this->_referrer->get_groups() ) );
		}

		return $referrer_groups;
	}

	/**
	 * Returns array of encryptions
	 *
	 * @return array
	 */
	function _get_encryptions() {
		$is_https = ( substr( get_home_url(), 0, 5 ) == 'https' );

		$encryptions = array();

		if ( ! $is_https || $this->_config->get_boolean( 'pgcache.cache.ssl' ) )
			$encryptions[] = '';
		if ( $is_https || $this->_config->get_boolean( 'pgcache.cache.ssl' ) )
			$encryptions[] = 'ssl';

		return $encryptions;
	}



	private function _append_parent_terms( $terms, $terms_to_check_parents ) {
		$terms_to_check_parents = $terms;
		$ids = null;

		for ( ;; ) {
			$parent_ids = array();
			$taxonomies = array();

			foreach ( $terms_to_check_parents as $term ) {
				if ( $term->parent ) {
					$parent_ids[$term->parent] = '*';
					$taxonomies[$term->taxonomy] = '*';
				}
			}

			if ( empty( $parent_ids ) )
				return $terms;

			if ( is_null( $ids ) ) {
				// build a map of ids for faster check
				$ids = array();
				foreach ( $terms as $term )
					$ids[$term->term_id] = '*';
			} else {
				// append last new items to ids map
				foreach ( $terms_to_check_parents as $term )
					$ids[$term->term_id] = '*';
			}

			// build list to extract
			$include_ids = array();

			foreach ( $parent_ids as $id => $v ) {
				if ( !isset( $ids[$id] ) )
					$include_ids[] = $id;
			}

			if ( empty( $include_ids ) )
				return $terms;

			$new_terms = get_terms( array_keys( $taxonomies ),
				array( 'include' => $include_ids ) );

			$terms = array_merge( $terms, $new_terms );
			$terms_to_check_parents = $new_terms;
		}
	}
}
