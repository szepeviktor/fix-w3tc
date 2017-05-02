<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<div class="sign-up w3tc-widget-swarmify-base">
    <p><?php _e( 'Just as the load time and overall performance of your website impacts user satisfaction, so does the performance of your online videos. Optimize your video performance by enabling the Swarmify SmartVideo&#8482 solution.', 'w3-total-cache' )?></p>
    <h4><?php _e( 'New customers', 'w3-total-cache' )?></h4>
    <p><?php _e( 'Swarmify is a service that lets you speed up your site even more with W3 Total Cache.', 'w3-total-cache' )?></p>
    <a class="button-primary" href="<?php echo $swarmify_signup_url ?>" target="_blank"><?php _e( 'Sign Up Now and Save 25%', 'w3-total-cache' )?></a>
    <p><span class="desc"><?php _e( 'Free 14 day limited trial', 'w3-total-cache' )?></span></p>
    <h4><?php _e( 'Current customers', 'w3-total-cache' )?></h4>
    <p><?php _e( 'If you already have a Swarmify configuration key, or need to update your existing key, click here:', 'w3-total-cache' )?></p>
    <a class="button-primary" href="admin.php?page=w3tc_extensions&amp;extension=swarmify&amp;action=view"><?php _e( 'Configure', 'w3-total-cache' )?></a>
</div>
