<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();
?>
<form class="w3tc_popup_form" method="post">
    <?php
Util_Ui::hidden( '', 'access_key', $details['access_key'] );
Util_Ui::hidden( '', 'secret_key', $details['secret_key'] );
Util_Ui::hidden( '', 'distribution_id', $details['distribution_id'] );
Util_Ui::hidden( '', 'distribution_comment', $details['distribution_comment'] );
?>

    <div class="metabox-holder">
        <?php Util_Ui::postbox_header( __( 'Configure distribution', 'w3-total-cache' ) ); ?>
        <table class="form-table">
            <tr>
                <th>Distribution:</th>
                <td><?php echo $details['distribution_comment'] ?></td>
            </tr>
            <tr>
                <th>Origin:</th>
                <td><?php $this->render_zone_ip_change( $details, 'origin' ) ?><br />
                    <span class="description">
                        Create an apex <acronym title="Domain Name System">DNS</acronym> record pointing to your WordPress host <acronym title="Internet Protocol">IP</acronym>.
                        CloudFront will use this host to mirror your site.

                        Tip: If you real domain name is domain.com, then the host 
                        for the apex record should be origin.domain.com with the host 
                        <acronym title="Internet Protocol">IP</acronym> of domain.com, e.g.: 
                    </span>
                </td>
            </tr>
            <tr>
                <th>Alias Domain:</th>
                <td><?php $this->render_zone_value_change( $details, 'alias' ) ?></td>
            </tr>
            <tr>
                <th>Forward Cookies:</th>
                <td><?php $this->render_zone_boolean_change( $details, 'forward_cookies' ) ?></td>
            </tr>
            <tr>
                <th>Forward Query String:</th>
                <td><?php $this->render_zone_boolean_change( $details, 'forward_querystring' ) ?></td>
            </tr>
            <tr>
                <th>Forward Host Header:</th>
                <td><?php $this->render_zone_boolean_change( $details, 'forward_host' ) ?></td>
            </tr>
        </table>

        <p class="submit">
            <input type="button"
                class="w3tc_cdn_cloudfront_fsd_configure_distribution w3tc-button-save button-primary"
                value="<?php _e( 'Apply', 'w3-total-cache' ); ?>" />
            <input type="button"
                class="w3tc_cdn_cloudfront_fsd_configure_distribution_skip w3tc-button-save button"
                value="<?php _e( 'Don\'t reconfigure, I know what I\'m doing', 'w3-total-cache' ); ?>" />
        </p>
        <?php Util_Ui::postbox_footer(); ?>
    </div>
</form>
