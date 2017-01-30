<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<form action="" xmlns="http://www.w3.org/1999/html" method="post">
    <textarea id="purge_urls" name="purge_urls" data-nonce="<?php echo wp_create_nonce( 'w3tc' ); ?>"></textarea>
    <p> Enter each URL on a new line </p>
<p>
    <input id="submit_purge_urls" type="submit" class="button button-primary button-large" value="<?php _e( 'Purge', 'w3-total-cache' ) ?>"/>
</p>
</form>
