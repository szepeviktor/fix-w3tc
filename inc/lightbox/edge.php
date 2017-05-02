<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<div id="w3tc-edge-mode">
    <div class="w3tc-overlay-logo"></div>
    <header>
    </header>
    <div class="content">
    <p><strong><?php _e( 'Enable "Edge Mode" to opt-in to pre-release features or simply close this window to continue to enjoy bug fixes, security fixes and stable updates only.', 'w3-total-cache' ) ?></strong></p>
    <p><?php _e( 'We want to ensure that those who are interested in ongoing performance optimizations always have access to the latest functionality and optimization techniques. Those who enable edge mode should have experience in troubleshooting WordPress installations.', 'w3-total-cache' ) ?></p>
    <p><?php _e( 'Please be aware that by enabling "Edge Mode" you are also accepting to send <strong><u>anonymous tracking usage</u></strong>.', 'w3-total-cache' ) ?></p>
     </div>
    <div class="w3tc_overlay_footer">
        <?php
echo Util_Ui::action_button( __( 'Enable Edge Mode', 'w3-total-cache' ),
	Util_Ui::url( array( 'w3tc_edge_mode_enable' => 'y' ) ),
	"btn w3tc-size image btn-default palette-turquoise" )
?>
        <input type="button" class="btn w3tc-size btn-default outset palette-light-grey w3tc_lightbox_close" value="Cancel">
    </div>
</div>
