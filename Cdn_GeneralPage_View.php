<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

Util_Ui::postbox_header( __( '<acronym title="Content Delivery Network">CDN</acronym>', 'w3-total-cache' ), '', 'cdn' );
Util_Ui::config_overloading_button( array(
		'key' => 'cdn.configuration_overloaded'
	) );
?>
<p><?php _e( 'Host static files with your content delivery network provider to reduce page load time.', 'w3-total-cache' ); ?>
<?php if ( !$cdn_enabled ): ?>
<?php printf( __( ' If you do not have a <acronym title="Content Delivery Network">CDN</acronym> provider try MaxCDN. <a href="%s" target="_blank">Sign up and save 25&#37;</a>.', 'w3-total-cache' ), wp_nonce_url( Util_Ui::admin_url( 'admin.php?page=w3tc_dashboard&w3tc_cdn_maxcdn_signup' ), 'w3tc' ) ); ?>
<?php endif ?>
</p>
<table class="form-table">
    <?php
Util_Ui::config_item( array(
		'key' => 'cdn.enabled',
		'control' => 'checkbox',
		'checkbox_label' => __( 'Enable', 'w3-total-cache' ),
		'description' => __( 'Theme files, media library attachments, <acronym title="Cascading Style Sheet">CSS</acronym>, <acronym title="JavaScript">JS</acronym> files etc will appear to load instantly for site visitors.',
			'w3-total-cache' )
	) );

Util_Ui::config_item( array(
		'key' => 'cdn.engine',
		'control' => 'selectbox',
		'selectbox_values' => $engine_values,
		'selectbox_optgroups' => $engine_optgroups,
		'description' => __( 'Select the <acronym title="Content Delivery Network">CDN</acronym> type you wish to use.',
			'w3-total-cache' ) . $cdn_engine_extra_description
	) );
?>
</table>

<?php
Util_Ui::button_config_save( 'general_cdn',
	'<input id="cdn_purge" type="button" value="'.
	__( 'Empty cache', 'w3-total-cache' ) . '" ' .
	( $cdn_enabled && Cdn_Util::can_purge_all( $config->get_string( 'cdn.engine' ) ) ? '' :
		' disabled="disabled" ' ) .
	' class="button {nonce: \'' . wp_create_nonce( 'w3tc' ) . '\'}" />' );
?>
<?php Util_Ui::postbox_footer(); ?>
