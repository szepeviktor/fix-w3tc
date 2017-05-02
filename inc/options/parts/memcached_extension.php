<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

$config = Dispatcher::config();
/*
 * Requires $module variable
 */
?>
<tr>
    <th><label for="memcached_servers"><?php echo Util_ConfigLabel::get( 'memcached.servers' ) ?></label></th>
    <td>
        <input id="memcached_servers" type="text"
            name="<?php echo $module ?>___memcached__servers"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            value="<?php echo esc_attr( implode( ',', $config->get_array( array( $module, 'memcached.servers' ) ) ) ); ?>" size="80" />
        <input id="memcached_test" class="button {nonce: '<?php echo wp_create_nonce( 'w3tc' ); ?>'}"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            type="button" value="<?php esc_attr_e( 'Test', 'w3-total-cache' ); ?>" />
        <span id="memcached_test_status" class="w3tc-status w3tc-process"></span>
        <br /><span class="description"><?php _e( 'Multiple servers may be used and seperated by a comma; e.g. 192.168.1.100:11211, domain.com:22122', 'w3-total-cache' ); ?></span>
    </td>
</tr>
<?php

Util_Ui::config_item( array(
		'key' => array( $module, 'memcached.persistent' ),
		'label' => __( 'Use persistent connection:', 'w3-total-cache' ),
		'control' => 'checkbox',
		'checkbox_label' => Util_ConfigLabel::get( 'memcached.persistent' ),
		'description' =>
		'Using persistent connection doesn\'t reinitialize memcached driver on each request'
	) );

Util_Ui::config_item( array(
		'key' => array( $module, 'memcached.aws_autodiscovery' ),
		'label' => __( 'Node Auto Discovery:', 'w3-total-cache' ),
		'control' => 'checkbox',
		'checkbox_label' => 'Amazon Node Auto Discovery',
		'disabled' => ( Util_Installed::memcached_aws() ? null : true ),
		'description' =>
		( !Util_Installed::memcached_aws() ?
			__( 'ElastiCache <acronym title="Hypertext Preprocessor">PHP</acronym> module not found', 'w3-total-cache' ) :
			__( 'When Amazon ElastiCache used, specify configuration endpoint as Memecached host', 'w3-total-cache' )
		)
	) );

Util_Ui::config_item( array(
		'key' => array( $module, 'memcached.username' ),
		'label' => Util_ConfigLabel::get( 'memcached.username' ),
		'control' => 'textbox',
		'disabled' => ( Util_Installed::memcache_auth() ? null : true ),
		'description' =>
		__( 'Specify memcached username, when <acronym title="Simple Authentication and Security Layer">SASL</acronym> authentication used', 'w3-total-cache' ) .
		( Util_Installed::memcache_auth() ? '' :
			__( '<br>Available when memcached extension installed, built with <acronym title="Simple Authentication and Security Layer">SASL</acronym>, and memcached.use_sasl = 1 option is set in php.ini', 'w3-total-cache' )
		)
	) );

Util_Ui::config_item( array(
		'key' => array( $module, 'memcached.password' ),
		'label' => Util_ConfigLabel::get( 'memcached.password' ),
		'control' => 'textbox',
		'disabled' => ( Util_Installed::memcache_auth() ? null : true ),
		'description' =>
		__( 'Specify memcached password, when <acronym title="Simple Authentication and Security Layer">SASL</acronym> authentication used', 'w3-total-cache' )
	) );

?>
