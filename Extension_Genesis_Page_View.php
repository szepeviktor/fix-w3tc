<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<p id="w3tc-options-menu">
    Jump to:
    <a href="admin.php?page=w3tc_general"><?php _e( 'Main Menu', 'w3-total-cache' ); ?></a> |
    <a href="admin.php?page=w3tc_extensions"><?php _e( 'Extensions', 'w3-total-cache' ); ?></a> |
    <a href="#header"><?php _e( 'Header', 'w3-total-cache' ); ?></a> |
    <a href="#content"><?php _e( 'Content', 'w3-total-cache' ); ?></a> |
    <a href="#sidebar"><?php _e( 'Sidebar', 'w3-total-cache' ); ?></a> |
    <a href="#exclusions"><?php _e( 'Exclusions', 'w3-total-cache' ); ?></a>
</p>
<p>
    Genesis extension is currently <?php
if ( $config->is_extension_active_frontend( 'genesis.theme' ) )
	echo '<span class="w3tc-enabled">enabled</span>';
else
	echo '<span class="w3tc-disabled">disabled</span>';
?>.
<p>

<div class="metabox-holder">
    <?php Util_Ui::postbox_header( __( 'Header', 'w3-total-cache' ), '', 'header' ); ?>
    <table class="form-table">
        <?php
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'wp_head' ),
		'control' => 'checkbox',
		'label' => __( 'Cache wp_head loop:', 'w3-total-cache' ),
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'description' =>__( 'Cache wp_head. This includes the embedded CSS, JS etc.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'genesis_header' ),
		'control' => 'checkbox',
		'label' => __( 'Cache header:', 'w3-total-cache' ),
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'description' => __( 'Cache header loop. This is the area where the logo is located.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'genesis_do_nav' ),
		'control' => 'checkbox',
		'label' => __( 'Cache primary navigation:', 'w3-total-cache' ),
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'description' => __( 'Caches the navigation filter; per page.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'genesis_do_subnav' ),
		'control' => 'checkbox',
		'label' => __( 'Cache secondary navigation:', 'w3-total-cache' ),
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'description' => __( 'Caches secondary navigation filter; per page.', 'w3-total-cache' )
	) );

?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_genesis_header' ); ?>
    <?php Util_Ui::postbox_footer(); ?>



    <?php Util_Ui::postbox_header( __( 'Content', 'w3-total-cache' ), '', 'content' ); ?>
    <table class="form-table">
        <?php
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_front_page' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache front page post loop:', 'w3-total-cache' ),
		'description' => __( 'Caches the front page post loop, pagination is supported.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_terms' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache author/tag/categories/term post loop:', 'w3-total-cache' ),
		'description' => __( 'Caches the posts listed on tag, categories, author and other term pages, pagination is supported.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_terms_excluded' ),
		'control' => 'textarea',
		'label' => __( 'Excluded terms pages / posts:', 'w3-total-cache' ),
		'description' => __( 'List of pages / posts that should not have the terms loop cached. Specify one page / post per line. This area supports regular expressions.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'flush_terms' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Flush posts loop:', 'w3-total-cache' ),
		'description' => __( 'Flushes the posts loop cache on post updates. See setting above for affected loops.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_single' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache single post / page:', 'w3-total-cache' ),
		'description' => __( 'Caches the single post / page loop, pagination is supported.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_single_excluded' ),
		'control' => 'textarea',
		'label' => __( 'Excluded single pages / posts:', 'w3-total-cache' ),
		'description' => __( 'List of pages / posts that should not have the single post / post loop cached. Specify one page / post per line. This area supports regular expressions.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_single_genesis_comments' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache comments:', 'w3-total-cache' ),
		'description' => __( 'Caches the comments loop, pagination is supported.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'loop_single_genesis_pings' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache pings:', 'w3-total-cache' ),
		'description' => __( 'Caches the ping loop, pagination is supported. One per line.', 'w3-total-cache' )
	) );
?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_genesis_content' ); ?>
    <?php Util_Ui::postbox_footer(); ?>



    <?php Util_Ui::postbox_header( __( 'Sidebar', 'w3-total-cache' ), '', 'sidebar' ); ?>
    <table class="form-table">
        <?php
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'sidebar' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache sidebar:', 'w3-total-cache' ),
		'description' => __( 'Caches sidebar loop, the widget area.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'sidebar_excluded' ),
		'control' => 'textarea',
		'label' => __( 'Exclude pages:', 'w3-total-cache' ),
		'description' => __( 'List of pages that should not have sidebar cached. Specify one page / post per line. This area supports regular expressions.', 'w3-total-cache' )
	) );
?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_genesis_sidebar' ); ?>
    <?php Util_Ui::postbox_footer(); ?>



    <?php Util_Ui::postbox_header( __( 'Footer', 'w3-total-cache' ) ); ?>
    <table class="form-table">
        <?php
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'genesis_footer' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache genesis footer:', 'w3-total-cache' ),
		'description' => __( 'Caches footer loop.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'wp_footer' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Cache footer:', 'w3-total-cache' ),
		'description' => __( 'Caches wp_footer loop.', 'w3-total-cache' )
	) );
Util_Ui::config_item( array(
		'key' => array( 'genesis.theme', 'reject_logged_roles' ),
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'label' => __( 'Disable fragment cache:', 'w3-total-cache' ),
		'description' => 'Don\'t use fragment cache with the following hooks and for the specified user roles.'
	) );
?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_genesis_footer' ); ?>
    <?php Util_Ui::postbox_footer(); ?>


    <?php Util_Ui::postbox_header( __( 'Exclusions', 'w3-total-cache' ), '', 'exclusions' ); ?>
    <table class="form-table">
        <tr>
            <td><?php _e( 'Select hooks', 'w3-total-cache' ) ?></td>
            <td>
                <?php

$saved_hooks = $config->get_array( array( 'genesis.theme', 'reject_logged_roles_on_actions' ) );
$name = Util_Ui::config_key_to_http_name( array( 'genesis.theme', 'reject_logged_roles_on_actions' ) );
$hooks = array(
	'genesis_header' => 'Header',
	'genesis_footer' => 'Footer',
	'genesis_sidebar' => 'Sidebar',
	'genesis_loop' =>'The Loop',
	'wp_head' => 'wp_head',
	'wp_footer' => 'wp_footer',
	'genesis_comments' => 'Comments',
	'genesis_pings' => 'Pings',
	'genesis_do_nav'=>'Primary navigation',
	'genesis_do_subnav' => 'Secondary navigation'
);
?>

                <input <?php disabled( $config->is_sealed( 'genesis.theme' ) ) ?>
                    type="hidden" name="<?php echo esc_attr( $name )?>" value="" />
                <?php foreach ( $hooks as $hook => $hook_label ) : ?>
                    <input <?php disabled( $config->is_sealed( 'genesis.theme' ) ) ?>
                        type="checkbox" name="<?php echo esc_attr( $name )?>[]"
                        value="<?php echo $hook ?>"
                        <?php checked( in_array( $hook, $saved_hooks ) ) ?>
                        id="role_<?php echo $hook ?>" />
                    <label for="role_<?php echo $hook ?>"><?php echo $hook_label ?></label><br />
                <?php endforeach; ?>

                <br />
                <span class="description">
                    <?php _e( 'Select hooks from the list that should not be cached if user belongs to any of the roles selected below.', 'w3-total-cache' ) ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><?php _e( 'Select roles:', 'w3-total-cache' ) ?></td>
            <td>
                <?php
$saved_roles = $config->get_array( array( 'genesis.theme', 'reject_roles' ) );
$name = Util_Ui::config_key_to_http_name( array( 'genesis.theme', 'reject_roles' ) );

?>
                <input type="hidden" name="<?php echo esc_attr( $name )?>" value="" />
                <?php foreach ( get_editable_roles() as $role_name => $role_data ) : ?>
                    <input <?php disabled( $config->is_sealed( 'genesis.theme' ) ) ?>
                        type="checkbox"
                        name="<?php echo esc_attr( $name )?>[]"
                        value="<?php echo $role_name ?>" <?php checked( in_array( $role_name, $saved_roles ) ) ?> id="role_<?php echo $role_name ?>" />
                    <label for="role_<?php echo $role_name ?>"><?php echo $role_data['name'] ?></label>
                <?php endforeach; ?>
                <br />
                <span class="description">
                    <?php _e( 'Select user roles that should not use the fragment cache.', 'w3-total-cache' ) ?>
                </span>
            </td>
        </tr>
    </table>
    <?php Util_Ui::button_config_save( 'extension_genesis_exclusions' ); ?>
    <?php Util_Ui::postbox_footer(); ?>

</div>
