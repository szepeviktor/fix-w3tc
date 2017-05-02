<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<p>
    Jump to:
    <a href="admin.php?page=w3tc_general"><?php _e( 'Main Menu', 'w3-total-cache' ); ?></a> |
    <a href="admin.php?page=w3tc_extensions"><?php _e( 'Extensions', 'w3-total-cache' ); ?></a>
</p>
<p>
    Swarmify extension is currently <?php
if ( $config->is_extension_active_frontend( 'swarmify' ) )
	echo '<span class="w3tc-enabled">enabled</span>';
else
	echo '<span class="w3tc-disabled">disabled</span>';
?>.
<p>

<form action="admin.php?page=w3tc_extensions&amp;extension=swarmify&amp;action=view" method="post">
<div class="metabox-holder">
    <?php Util_Ui::postbox_header( __( 'Configuration', 'w3-total-cache' ),
'', 'configuration' ); ?>
    <table class="form-table">
		<?php
		Util_Ui::config_item( array(
				'key' => array( 'swarmify', 'api_key' ),
				'label' => __( 'API Key:', 'w3-total-cache' ),
				'control' => 'textbox',
				'control_after' => Util_Ui::button_link( 'Obtain one', $swarmify_signup_url ),
				'description' =>
				'Swarmify API Key required in order to start optimizing your videos experience'
			)
		);
		?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_swarmify_configuration' ); ?>
    <?php Util_Ui::postbox_footer(); ?>



    <?php Util_Ui::postbox_header( __( 'Behavior Settings', 'w3-total-cache' ), '', 'behavior' ); ?>
    <table class="form-table">
        <?php
		Util_Ui::config_item( array(
				'key' => array( 'swarmify', 'handle.htmlvideo' ),
				'label' => __( '&lt;video&gt;:', 'w3-total-cache' ),
				'control' => 'checkbox',
				'checkbox_label' => "Optimize &lt;video&gt; HTML tags",
				'description' =>
				'Optimize videos delivered using &lt;video&gt; HTML tag.'
			)
		);
		?>
        <?php
		Util_Ui::config_item( array(
				'key' => array( 'swarmify', 'handle.jwplayer' ),
				'label' => __( 'JWPlayer:', 'w3-total-cache' ),
				'control' => 'checkbox',
				'checkbox_label' => "Optimize JWPlayer",
				'description' =>
				'Optimize videos delivered using JWPlayer script.'
			)
		);
		?>
        <?php
		Util_Ui::config_item( array(
				'key' => array( 'swarmify', 'reject.logged' ),
				'label' => __( 'Logged In:', 'w3-total-cache' ),
				'control' => 'checkbox',
				'checkbox_label' => "Don't optimize videos for logged in users",
				'description' =>
				'Only unauthenticated users will view optimized version of a given page.'
			)
		);
		?>
    </table>
    <?php Util_Ui::button_config_save( 'extension_swarmify_behaviour' ); ?>
    <?php Util_Ui::postbox_footer(); ?>
    </form>
</div>
