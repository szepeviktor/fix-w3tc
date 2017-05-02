<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<p>
	<?php
echo Util_Ui::button_link(
	__( 'Purge CDN completely', 'w3-total-cache' ),
	Util_Ui::url( array( 'w3tc_cdn_flush' => 'y' ) ) );
?>
</p>
