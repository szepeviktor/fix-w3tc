<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

$is_pro = Util_Environment::is_w3tc_pro( $this->_config );

?>
<?php $this->checkbox( 'minify.css.strip.comments', false, 'css_' ) ?> <?php Util_Ui::e_config_label( 'minify.css.strip.comments' ) ?></label><br />
<?php $this->checkbox( 'minify.css.strip.crlf', false, 'css_' ) ?> <?php Util_Ui::e_config_label( 'minify.css.strip.crlf' ) ?></label><br />
<?php $this->checkbox( 'minify.css.embed', !$is_pro, 'csse_', true, ( $is_pro ? null : false ) ) ?> Eliminate render-blocking <acronym title="Cascading Style Sheet">CSS</acronym> by moving it to <acronym title="Hypertext Markup Language">HTTP</acronym> body</label>
<?php
if ( !$is_pro )
	echo ' (Available after <a href="#" class="button-buy-plugin">upgrade</a>)';
?>
<br />
