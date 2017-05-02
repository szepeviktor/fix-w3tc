<?php
namespace W3TC;

class UsageStatistics_ConfigLabels {
	public function config_labels( $config_labels ) {
		return array_merge( $config_labels, array(
				'stats.enabled' => __( 'Collect and display usage statistics', 'w3-total-cache' ),
			) );
	}
}
