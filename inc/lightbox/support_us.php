<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<div id="w3tc-support-us">
    <div class="w3tc-overlay-logo"></div>
    <header>
        <div class="left" style="float:left">
            <h2>Frederick Townes</h2>
            <h3>CEO, W3 EDGE</h3>
        </div>
        <div class="right" style="float:right">
            <div style="display: inline-block">
                <iframe height="21" width="100" src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2FW3EDGE&amp;width=100&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=true&amp;send=false&amp;appId=53494339074" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>
            </div>
            <div style="display: inline-block; margin-left: 10px;">
                <a href="https://twitter.com/w3edge" class="twitter-follow-button" data-show-count="true" data-show-screen-name="false" target="_blank">Follow @w3edge</a>
                <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
            </div>
            <div style="display: inline-block; margin-left: 10px;">
                <!-- Place this tag where you want the widget to render. -->
                <div class="g-follow" data-annotation="bubble" data-height="20" data-href="https://plus.google.com/106009620651385224281" data-rel="author"></div>
                <!-- Place this tag after the last widget tag. -->
                <script type="text/javascript">
                    (function() {
                        var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                        po.src = 'https://apis.google.com/js/plusone.js';
                        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                    })();
                </script>
            </div>
        </div>
    </header>
    <form action="<?php echo Util_Ui::admin_url( 'admin.php?page=w3tc_general' ); ?>&amp;w3tc_config_save_support_us" method="post">

    <div class="content">
            <h3 class="font-palette-dark-skies"><?php _e( 'Support Us, It\'s Free!', 'w3-total-cache' ); ?></h3>

            <p><?php _e( 'We noticed you\'ve been using W3 Total Cache for 30 days or more, please help us improve WordPress by:', 'w3-total-cache' ); ?></p>
            <ul>
                <li>
                    <label>
                        Link to us: <br />
                        <div class="styled-select">
                            <select name="support" class="w3tc-size select">
                                <option value=""><?php esc_attr_e( 'select location', 'w3-total-cache' ); ?></option>
                                <?php foreach ( $supports as $support_id => $support_name ): ?>
                                    <option value="<?php echo esc_attr( $support_id ); ?>"<?php echo selected( $this->_config->get_string( 'common.support' ), $support_id ); ?>><?php echo htmlspecialchars( $support_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </label>
                </li>
                <li>
                    <label>Send a tweet:<br />
                        <?php
$tweet_url = 'http://twitter.com/home/?status=' . urlencode( W3TC_SUPPORT_US_TWEET );
echo Util_Ui::action_button(
	__( 'Tell Your Friends', 'w3-total-cache' ),
	$tweet_url,
	"btn w3tc-size image btn-default palette-twitter",
	true );
echo Util_Ui::hidden(
	__( 'tweeted' ),
	__( 'tweeted' ),
	'0' ) ?>
                    </label>
                </li>
                <li>
                    <label>
                        Login to wordpress.org to give us a great rating:<br>
                        <?php
echo Util_Ui::action_button(
	__( 'Login & Rate Us', 'w3-total-cache' ),
	W3TC_SUPPORT_US_RATE_URL,
	"btn w3tc-size image btn-default palette-wordpress",
	true ) ?>
                    </label>
                </li>
            </ul>
            <p>
            <label class="w3tc_signup_email" for="email">You can also sign up for our newsletter:<br />
                <input id="email" name="email" type="text" class="form-control w3tc-size" value="<?php esc_attr_e( $email ) ?>"></label><br />
            <input type="checkbox" name="signmeup" id="signmeup" class="css-checkbox" value="1" checked="checked" /><label for="signmeup" class="css-label"> <?php _e( 'Yes, sign me up.', 'w3-total-cache' ) ?> </label>
            </p>
            <p>
            <input type="checkbox" name="track_usage" id="track_usage" class="css-checkbox" value="1"
                checked="checked" />
            <label for="track_usage" class="css-label">
                <?php _e( 'Anonymously track usage to improve product quality.', 'w3-total-cache' ) ?>
            </label>
            </p>
    </div>
    <div class="w3tc_overlay_footer">
        <p>
            <?php wp_nonce_field( 'w3tc' ) ?>
            <input type="submit" class="btn w3tc-size image w3tc-button-save btn-primary outset save palette-turquoise " value="Save &amp; close">
            <input type="button" class="btn w3tc-size btn-default outset palette-light-grey w3tc_lightbox_close" value="Cancel">
        </p>
    </div>
    </form>
</div>
