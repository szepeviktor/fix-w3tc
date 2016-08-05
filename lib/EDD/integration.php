<?php

// Remove HTML comment
if( !(defined('WP_DEBUG') && WP_DEBUG) ){
	add_filter( 'w3tc_can_print_comment', '__return_false' );
}

// Remove contextual help
add_action( 'admin_enqueue_scripts', 'o1_remove_w3tc_contextual_help' );
function o1_remove_w3tc_contextual_help( $hook ) {

    if ( 'performance_page_w3tc_' === substr( $hook, 0, 22 )
        || 'toplevel_page_w3tc_dashboard' === $hook
    ) {
        remove_all_filters( 'contextual_help_list' );
    }
}
