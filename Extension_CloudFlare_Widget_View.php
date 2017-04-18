<?php
namespace W3TC;

if ( !defined( 'W3TC' ) )
	die();

?>
<?php if ( is_null( $stats ) ): ?>
<?php _e( 'You have not configured your email, API key or domain', 'w3-total-cache' ) ?>
<?php else: ?>

<p class="cloudflare_p">
    Period
    <?php $this->time_mins( $stats, 'since' ) ?>
      -
    <?php $this->time_mins( $stats, 'until' ) ?>
</p>
<table class="cloudflare_table">
    <tr>
        <td></td>
        <td class="cloudflare_td_header">All</td>
        <td class="cloudflare_td_header">Cached</td>
    </tr>
    <tr>
        <td class="cloudflare_td">Bandwidth</td>
        <?php $this->v( $stats, 'bandwidth', 'all' ) ?>
        <?php $this->v( $stats, 'bandwidth', 'cached' ) ?>
    </tr>
    <tr>
        <td class="cloudflare_td">Requests</td>
        <?php $this->v( $stats, 'requests', 'all' ) ?>
        <?php $this->v( $stats, 'requests', 'cached' ) ?>
    </tr>
    <tr>
        <td class="cloudflare_td">Page Views</td>
        <?php $this->v( $stats, 'pageviews', 'all' ) ?>
    </tr>
    <tr>
        <td class="cloudflare_td">Uniques</td>
        <?php $this->v( $stats, 'uniques', 'all' ) ?>
    </tr>
    <tr>
        <td class="cloudflare_td">Threats</td>
        <?php $this->v( $stats, 'threats', 'all' ) ?>
    </tr>
</table>

<?php endif; ?>
