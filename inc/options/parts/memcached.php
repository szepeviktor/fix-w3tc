<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

/*
 * Requires $module variable
 */
?>
<tr>
    <th><label for="memcached_servers"><?php echo Util_ConfigLabel::get( 'memcached.servers' ) ?></label></th>
    <td>
        <input id="memcached_servers" type="text"
            name="<?php echo $module ?>__memcached__servers"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            value="<?php echo esc_attr( implode( ',', $this->_config->get_array( $module . '.memcached.servers' ) ) ); ?>" size="80" />
        <input id="memcached_test" class="button {nonce: '<?php echo wp_create_nonce( 'w3tc' ); ?>'}"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            type="button" value="<?php esc_attr_e( 'Test', 'w3-total-cache' ); ?>" />
        <span id="memcached_test_status" class="w3tc-status w3tc-process"></span>
        <br /><span class="description"><?php _e( 'Multiple servers may be used and seperated by a comma; e.g. 192.168.1.100:11211, domain.com:22122', 'w3-total-cache' ); ?></span>
    </td>
</tr>
<tr>
    <th><label><?php _e( 'Use persistent connection:', 'w3-total-cache' ); ?></label></th>
    <td>
        <?php $this->checkbox( $module . '.memcached.persistent' ) ?> <?php echo Util_ConfigLabel::get( 'memcached.persistent' ) ?></label><br />
        <span class="description"><?php _e( 'Using persistent connection doesn\'t reinitialize memcached driver on each request', 'w3-total-cache' ); ?></span>
    </td>
</tr>
<tr>
    <th><label><?php _e( 'Node Auto Discovery:', 'w3-total-cache' ); ?></label></th>
    <td>
        <label><?php $this->checkbox( $module . '.memcached.aws_autodiscovery',
	!Util_Installed::memcached_aws() ) ?>
        Amazon Node Auto Discovery</label><br />
        <span class="description">
            <?php
if ( !Util_Installed::memcached_aws() )
	_e( 'ElastiCache <acronym title="Hypertext Preprocessor">PHP</acronym> module not found', 'w3-total-cache' );
else
	_e( 'When Amazon ElastiCache used, specify configuration endpoint as Memcached host', 'w3-total-cache' );
?>
        </span>
    </td>
</tr>

<tr>
    <th><label for="memcached_username"><?php echo Util_ConfigLabel::get( 'memcached.username' ) ?></label></th>
    <td>
        <input id="memcached_username" name="<?php echo $module ?>__memcached__username" type="text"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            <?php
$this->value_with_disabled( $module . '.memcached.username',
	!Util_Installed::memcached_auth(), '' )
?> /><br />
        <span class="description">
            <?php _e( 'Specify memcached username, when <acronym title="Simple Authentication and Security Layer">SASL</acronym> authentication used', 'w3-total-cache' );
if ( !Util_Installed::memcached_auth() )
	_e( '<br>Available when memcached extension installed, built with <acronym title="Simple Authentication and Security Layer">SASL</acronym>, and memcached.use_sasl = 1 option is set in php.ini', 'w3-total-cache' )
	?></span>
    </td>
</tr>
<tr>
    <th><label for="memcached_password"><?php echo Util_ConfigLabel::get( 'memcached.password' ) ?></label></th>
    <td>
        <input id="memcached_password" name="<?php echo $module ?>__memcached__password" type="text"
            <?php Util_Ui::sealing_disabled( $module ) ?>
            <?php
	$this->value_with_disabled( $module . '.memcached.password',
		!Util_Installed::memcached_auth(), '' )
	?> /><br />
        <span class="description"><?php _e( 'Specify memcached password, when <acronym title="Simple Authentication and Security Layer">SASL</acronym> authentication used', 'w3-total-cache' )?></span>
    </td>
</tr>
