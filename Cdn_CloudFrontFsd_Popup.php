<?php
namespace W3TC;



class Cdn_CloudFrontFsd_Popup {
	static public function w3tc_ajax() {
		$o = new Cdn_CloudFrontFsd_Popup();

		add_action( 'w3tc_ajax_cdn_cloudfront_fsd_intro',
			array( $o, 'w3tc_ajax_cdn_cloudfront_fsd_intro' ) );
		add_action( 'w3tc_ajax_cdn_cloudfront_fsd_list_distributions',
			array( $o, 'w3tc_ajax_cdn_cloudfront_fsd_list_distributions' ) );
		add_action( 'w3tc_ajax_cdn_cloudfront_fsd_view_distribution',
			array( $o, 'w3tc_ajax_cdn_cloudfront_fsd_view_distribution' ) );
		add_action( 'w3tc_ajax_cdn_cloudfront_fsd_configure_distribution',
			array( $o, 'w3tc_ajax_cdn_cloudfront_fsd_configure_distribution' ) );
		add_action( 'w3tc_ajax_cdn_cloudfront_fsd_configure_distribution_skip',
			array( $o, 'w3tc_ajax_cdn_cloudfront_fsd_configure_distribution_skip' ) );
	}



	public function w3tc_ajax_cdn_cloudfront_fsd_intro() {
		$this->render_intro( array() );
	}



	private function render_intro( $details ) {
		$config = Dispatcher::config();
		$url_obtain_key = Util_Ui::url( array(
				'page' => 'w3tc_dashboard',
				'w3tc_cdn_maxcdn_authorize' => 'y'
			) );

		include  W3TC_DIR . '/Cdn_CloudFrontFsd_Popup_View_Intro.php';
		exit();
	}



	public function w3tc_ajax_cdn_cloudfront_fsd_list_distributions() {
		$access_key = $_REQUEST['access_key'];
		$secret_key = $_REQUEST['secret_key'];

		$api = new Cdn_CloudFrontFsd_Api( $access_key, $secret_key );
		if ( empty( $access_key ) || empty( $secret_key ) ) {
			$this->render_intro( array(
					'error_message' => 'Can\'t authenticate: Access Key or Secret not valid'
				) );
			exit();
		}

		try {
			$distributions = $api->distributions_list();
		} catch ( \Exception $ex ) {
			$error_message = 'Can\'t authenticate: ' . $ex->getMessage();

			$this->render_intro( array(
					'error_message' => $error_message
				) );
			exit();
		}

		$items = array();

		if ( isset( $distributions['Items']['DistributionSummary'] ) ) {
			foreach ( $distributions['Items']['DistributionSummary'] as $i ) {
				if ( empty( $i['Comment'] ) )
					$i['Comment'] = $i['DomainName'];
				if ( isset( $i['Origins']['Items']['Origin'] ) )
					$i['Origin_DomainName'] = $i['Origins']['Items']['Origin'][0]['DomainName'];

				$items[] = $i;
			}
		}

		$details = array(
			'access_key' => $access_key,
			'secret_key' => $secret_key,
			'distributions' => $items
		);

		include  W3TC_DIR . '/Cdn_CloudFrontFsd_Popup_View_Distributions.php';
		exit();
	}



	public function w3tc_ajax_cdn_cloudfront_fsd_view_distribution() {
		$access_key = $_REQUEST['access_key'];
		$secret_key = $_REQUEST['secret_key'];
		$distribution_id = Util_Request::get( 'distribution_id', '' );

		$details = array(
			'access_key' => $access_key,
			'secret_key' => $secret_key,
			'distribution_id' => $distribution_id,
			'distribution_comment' => '',
			'origin' => array(
				'new' => ''
			),
			'forward_querystring' => array(
				'new' => true
			),
			'forward_cookies' => array(
				'new' => true
			),
			'forward_host' => array(
				'new' => true
			),
			'alias' => array(
				'new' => Util_Environment::home_url_host()
			)
		);

		if ( empty( $distribution_id ) ) {
			// create new zone mode
			$details['distribution_comment'] = Util_Request::get( 'comment_new' );
		} else {
			$api = new Cdn_CloudFrontFsd_Api( $access_key, $secret_key );

			try {
				$distribution = $api->distribution_get( $distribution_id );
			} catch ( \Exception $ex ) {
				$this->render_intro( array(
						'error_message' => 'Can\'t obtain zone: ' . $ex->getMessage()
					) );
				exit();
			}

			if ( isset( $distribution['DistributionConfig'] ) )
				$c = $distribution['DistributionConfig'];
			else
				$c = array();

			if ( !empty( $c['Comment'] ) )
				$details['distribution_comment'] = $c['Comment'];
			else
				$details['distribution_comment'] = $c['DomainName'];

			if ( isset( $c['Origins']['Items']['Origin'] ) ) {
				$details['origin']['current'] =
					$c['Origins']['Items']['Origin'][0]['DomainName'];
				$details['origin']['new'] = $details['origin']['current'];
			}

			if ( isset( $c['DefaultCacheBehavior'] ) &&
				isset( $c['DefaultCacheBehavior']['ForwardedValues'] ) )
				$b = $c['DefaultCacheBehavior']['ForwardedValues'];
			else
				$b = array();

			$details['forward_querystring']['current'] =
				( isset( $b['QueryString'] ) && $b['QueryString'] == 'true' );
			$details['forward_cookies']['current'] =
				( isset( $b['Cookies'] ) && isset( $b['Cookies']['Forward'] ) &&
				$b['Cookies']['Forward'] == 'all' );

			if ( isset( $c['Aliases']['Items']['CNAME'][0] ) )
				$details['alias']['current'] = $c['Aliases']['Items']['CNAME'][0];

			$details['forward_host']['current'] = false;
			if ( isset( $b['Headers']['Items']['Name'] ) ) {
				foreach ( $b['Headers']['Items']['Name'] as $name )
					if ( $name == 'Host' )
						$details['forward_host']['current'] = true;
			}
		}



		include  W3TC_DIR . '/Cdn_CloudFrontFsd_Popup_View_Distribution.php';
		exit();
	}



	private function render_zone_value_change( $details, $field ) {
		Util_Ui::hidden( '', $field, $details[$field]['new'] );

		if ( !isset( $details[$field]['current'] ) ||
			$details[$field]['current'] == $details[$field]['new'] )
			echo htmlspecialchars( $details[$field]['new'] );
		else {
			echo 'currently set to <strong>' .
				htmlspecialchars( empty( $details[$field]['current'] ) ?
				'<empty>' : $details[$field]['current'] ) .
				'</strong><br />';
			echo 'will be changed to <strong>' .
				htmlspecialchars( $details[$field]['new'] ) . '</strong><br />';
		}
	}



	private function render_zone_boolean_change( $details, $field ) {
		Util_Ui::hidden( '', $field, $details[$field]['new'] );

		if ( !isset( $details[$field]['current'] ) ) {
			echo 'will be set to <strong>';
			echo $this->render_zone_boolean( $details[$field]['new'] );
			echo '</strong>';
		} else if ( $details[$field]['current'] == $details[$field]['new'] ) {
				echo '<strong>';
				echo $this->render_zone_boolean( $details[$field]['new'] );
				echo '</strong>';
			} else {
			echo 'currently set to <strong>';
			$this->render_zone_boolean( $details[$field]['current'] );
			echo '</strong><br />';
			echo 'will be changed to <strong>';
			$this->render_zone_boolean( $details[$field]['new'] );
			echo '</strong><br />';
		}
	}



	private function render_zone_boolean( $v ) {
		if ( $v == 0 )
			echo 'disabled';
		else
			echo 'enabled';
	}



	private function render_zone_ip_change( $details, $field ) {
		Util_Ui::textbox( '', $field, $details[$field]['new'] );

		if ( isset( $details[$field]['current'] ) &&
			$details[$field]['current'] != $details[$field]['new'] ) {
			echo '<br /><span class="description">currently set to <strong>' .
				$details[$field]['current'] . '</strong></span>';
		}
	}



	public function w3tc_ajax_cdn_cloudfront_fsd_configure_distribution() {
		$access_key = $_REQUEST['access_key'];
		$secret_key = $_REQUEST['secret_key'];
		$distribution_id = Util_Request::get( 'distribution_id', '' );

		$origin_id = rand();

		$distribution = array(
			'Comment' => Util_Request::get( 'distribution_comment' ),
			'Origins' => array(
				'Quantity' => 1,
				'Items' => array(
					'Origin' => array(
						'Id' => $origin_id,
						'DomainName' => Util_Request::get( 'origin' ),
						'OriginPath' => '',
						'CustomOriginConfig' => array(
							'HTTPPort' => 80,
							'HTTPSPort' => 443,
							'OriginProtocolPolicy' => 'match-viewer'
						)
					)
				)
			),
			'Aliases' => array(
				'Quantity' => 1,
				'Items' => array(
					'CNAME' => Util_Request::get( 'alias' )
				)
			),
			'DefaultCacheBehavior' => array(
				'TargetOriginId' => $origin_id,
				'ForwardedValues' => array(
					'QueryString' => 'true',
					'Cookies' => array(
						'Forward' => 'all'
					),
					'Headers' => array(
						'Quantity' => 1,
						'Items' => array(
							'Name' => 'Host'
						)
					)
				),
				'AllowedMethods' => array(
					'Quantity' => 7,
					'Items' => array(
						'Method' => array(
							'GET', 'HEAD', 'OPTIONS', 'PUT', 'POST', 'PATCH',
							'DELETE'
						)
					),
					'CachedMethods' => array(
						'Quantity' => 2,
						'Items' => array(
							'Method' => array(
								'GET', 'HEAD'
							)
						)
					)
				),
				'MinTTL' => 0,
			)
		);

		try {
			$api = new Cdn_CloudFrontFsd_Api( $access_key, $secret_key );
			if ( empty( $distribution_id ) ) {
				$distribution['DefaultCacheBehavior']['TrustedSigners'] = array(
					'Enabled' => 'false',
					'Quantity' => 0
				);
				$distribution['DefaultCacheBehavior']['ViewerProtocolPolicy'] =
					'allow-all';

				$response = $api->distribution_create( $distribution );
				$distribution_id = $response['Id'];
			} else {
				$response = $api->distribution_update( $distribution_id, $distribution );
			}
		} catch ( \Exception $ex ) {
			$this->render_intro( array(
					'error_message' => 'Failed to configure distribution: ' . $ex->getMessage()
				) );
			exit();
		}

		$distribution_domain = $response['DomainName'];

		$c = Dispatcher::config();
		$c->set( 'cdn.cloudfront_fsd.access_key', $access_key );
		$c->set( 'cdn.cloudfront_fsd.secret_key', $secret_key );
		$c->set( 'cdn.cloudfront_fsd.distribution_id', $distribution_id );
		$c->set( 'cdn.cloudfront_fsd.distribution_domain', $distribution_domain );
		$c->save();

		$details = array(
			'name' => $distribution['Comment'],
			'home_domain' => Util_Environment::home_url_host(),
			'dns_cname_target' => $distribution_domain,
		);

		include  W3TC_DIR . '/Cdn_CloudFrontFsd_Popup_View_Success.php';
		exit();
	}



	public function w3tc_ajax_cdn_cloudfront_fsd_configure_distribution_skip() {
		$access_key = $_REQUEST['access_key'];
		$secret_key = $_REQUEST['secret_key'];
		$distribution_id = Util_Request::get( 'distribution_id', '' );

		$origin_id = rand();

		try {
			$api = new Cdn_CloudFrontFsd_Api( $access_key, $secret_key );
			$distribution = $api->distribution_get( $distribution_id );
		} catch ( \Exception $ex ) {
			$this->render_intro( array(
					'error_message' => 'Failed to configure distribution: ' . $ex->getMessage()
				) );
			exit();
		}

		if ( isset( $distribution['DomainName'] ) )
			$distribution_domain = $distribution['DomainName'];
		else
			$distribution_domain = 'n/a';

		$c = Dispatcher::config();
		$c->set( 'cdn.cloudfront_fsd.access_key', $access_key );
		$c->set( 'cdn.cloudfront_fsd.secret_key', $secret_key );
		$c->set( 'cdn.cloudfront_fsd.distribution_id', $distribution_id );
		$c->set( 'cdn.cloudfront_fsd.distribution_domain', $distribution_domain );
		$c->save();

		$details = array(
			'name' => $distribution['Comment'],
			'home_domain' => Util_Environment::home_url_host(),
			'dns_cname_target' => $distribution_domain,
		);

		include  W3TC_DIR . '/Cdn_CloudFrontFsd_Popup_View_Success.php';
		exit();
	}
}
