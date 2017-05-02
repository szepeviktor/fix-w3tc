<?php
class NetDNAOAuthClient extends OAuthClientBase
{
    /**
     * The NetDNA / MaxCDN url used to retrieve API ID and key
     *
     * @var string
     */
    protected $create_api_user_url;

    function __construct($cdn){
        parent::__construct($cdn);
        $this->create_api_user_url = W3TC_CDN_OAUTH_CREATE_API_USER;
    }

    /**
     * Returns the API credentials for the CDN.
     * Exists application and prints message on WP_Error.
     *
     * @return array
     */
    public function get_api_keys(){
        $token = W3_Request::get_string('token');
        $token_secret = W3_Request::get_string('token_secret');

        $consumer = new OAuthConsumer($this->key, $this->secret, NULL);
        $auth_token = new OAuthConsumer($token, $token_secret);
        $access_token_req = new OAuthRequest('GET', $this->access_token_url);
        $access_token_req = $access_token_req->from_consumer_and_token($consumer,
            $auth_token, 'GET', $this->access_token);

        $access_token_req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer,
            $auth_token);

        $after_access_request = wp_remote_get($access_token_req->to_url());

        parse_str($after_access_request['body'],$access_tokens);

        $access_token = new OAuthConsumer($access_tokens['oauth_token'], $access_tokens['oauth_token_secret']);

        $api_request = $access_token_req->from_consumer_and_token($consumer,
            $access_token, 'GET', $this->create_api_user_url, array('user_id' => $access_tokens['user_id']));

        $api_request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(),$consumer,$access_token);

        $page = wp_remote_get($api_request->to_url());

        if(is_wp_error($page))
            $this->handle_error($page);

        //Get API ID and API key
        parse_str ($page['body'],$credentials);

        return $credentials;
    }

    /**
     * Prints the required javascript for extracting API Credentials from page and close window.
     */
    public function print_javascript(){
        $api_keys=$this->get_api_keys();
        ?>
        <html>
        <body>
        <script type="text/javascript">
            var api_keys={<?php $arr=array(); foreach($api_keys as $key => $val) $arr[] = $key . ":'$val'"; echo implode(',',$arr)?>};
            var cdn_netdna_apiid = window.opener.document.getElementById('cdn_netdna_apiid');
            cdn_netdna_apiid.value=api_keys['api_id'];
            var cdn_netdna_apikey = window.opener.document.getElementById('cdn_netdna_apikey');
            cdn_netdna_apikey.value=api_keys['api_key'];
            window.close();
           //window.parent.jQuery('#oauthContainer').dialog('close');
        </script>
        </body>
        </html>
        <?php
    }
}
