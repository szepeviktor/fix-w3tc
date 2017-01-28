<?php
namespace W3TC;

/**
 * Attaches to wp actions related to content change, which should fire
 * flushes of html content
 */
class Util_AttachToActions {
	static function flush_posts_on_actions() {
		static $attached = false;
		if ( $attached )
			return;

		$attached = true;

		$o = new Util_AttachToActions();

		add_action( 'publish_phone', array(
				$o,
				'on_change'
			), 0 );

		add_action( 'wp_trash_post', array(
				$o,
				'on_post_change'
			), 0 );

		add_action( 'save_post', array(
				$o,
				'on_post_change'
			), 0 );

		add_action( 'comment_post', array(
				$o,
				'on_comment_change'
			), 0 );

		add_action( 'edit_comment', array(
				$o,
				'on_comment_change'
			), 0 );

		add_action( 'delete_comment', array(
				$o,
				'on_comment_change'
			), 0 );

		add_action( 'wp_set_comment_status', array(
				$o,
				'on_comment_status'
			), 0, 2 );

		add_action( 'trackback_post', array(
				$o,
				'on_comment_change'
			), 0 );

		add_action( 'pingback_post', array(
				$o,
				'on_comment_change'
			), 0 );

		add_action( 'delete_post', array(
				$o,
				'on_post_change'
			), 0 );

		add_action( 'clean_post_cache', array(
				$o,
				'on_post_change'
			), 0, 2 );
		add_action( 'publish_post', array(
				$o,
				'on_post_change'
			), 0, 2 );

		add_action( 'switch_theme', array(
				$o,
				'on_change'
			), 0 );

		add_action( 'wp_update_nav_menu', array(
				$o,
				'on_change'
			), 0 );

		add_action( 'edit_user_profile_update', array(
				$o,
				'on_change'
			), 0 );

		if ( Util_Environment::is_wpmu() ) {
			add_action( 'delete_blog', array(
					$o,
					'on_change'
				), 0 );
		}

		add_action( 'edited_term', array(
				$o,
				'on_change'
			), 0 );
	}



	/**
	 * Post changed action
	 *
	 * @param integer $post_id
	 * @param null    $post
	 * @return void
	 */
	function on_post_change( $post_id, $post = null ) {
		if ( is_null( $post ) )
			$post = get_post( $post_id );

		// if attachment changed - parent post has to be flushed
		// since there are usually attachments content like title
		// on the page (gallery)
		if ( $post->post_type == 'attachment' ) {
			$post_id = $post->post_parent;
			$post = get_post( $post_id );
		}

		if ( !Util_Environment::is_flushable_post( $post, 'posts',
				Dispatcher::config() ) )
			return;

		$cacheflush = Dispatcher::component( 'CacheFlush' );
		$cacheflush->flush_post( $post_id );
	}



	/**
	 * Change action
	 */
	function on_change() {
		$cacheFlush = Dispatcher::component( 'CacheFlush' );
		$cacheFlush->flush_posts();
	}



	/**
	 * Comment change action
	 *
	 * @param integer $comment_id
	 */
	function on_comment_change( $comment_id ) {
		$post_id = 0;

		if ( $comment_id ) {
			$comment = get_comment( $comment_id, ARRAY_A );
			$post_id = !empty( $comment['comment_post_ID'] ) ? (int) $comment['comment_post_ID'] : 0;
		}

		$this->on_post_change( $post_id );
	}



	/**
	 * Comment status action
	 *
	 * @param integer $comment_id
	 * @param string  $status
	 */
	function on_comment_status( $comment_id, $status ) {
		if ( $status === 'approve' || $status === '1' ) {
			$this->on_comment_change( $comment_id );
		}
	}	
}
