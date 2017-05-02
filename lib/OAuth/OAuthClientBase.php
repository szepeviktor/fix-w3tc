<?php
abstract class OAuthClientBase
{
    /**
     * The CDN providers OAuth Key for the W3tC Application
     *
     * @var string
     */
    protected $key;

    /**
     * The CDN providers OAuth Secret for the W3tC Application
     *
     * @var string
     */
    protected $secret;

    /**
     * The baseurl used in callbacks
     *
     * @var string
     */
    protected $base_url;

    /**
     * The CDN Providers authorize url
     *
     * @var string
     */
    protected $authorize_url;

    /**
     * The CDN Providers access token url
     * @var
     */
    protected $access_token_url;

    /**
     * The CDN Provider
     * @var string
     */
    protected $cdn;

    function __construct($cdn){
        require_once W3TC_LIB_OAUTH_DIR . '/config.php';

        $this->cdn = $cdn;
        $this->key = W3TC_CDN_OAUTH_KEY;
        $this->secret = W3TC_CDN_OAUTH_SECRET;
        $this->base_url = admin_url();
        $this->authorize_url = W3TC_CDN_OAUTH_AUTHORIZE_URL;
        $this->access_token = W3TC_CDN_OAUTH_ACCESS_TOKEN;
        $this->request_token_url = W3TC_CDN_OAUTH_REQUEST_TOKEN_URL;

    }

    /**
     * Requests and returns the authorization tokens. To be used with get_authorize_url.
     * Exits application and prints message on WP_Error
     *
     * @return array
     */
    private function _get_auth_request_tokens(){
        $test_consumer = new W3tcOAuthConsumer($this->key, $this->secret, NULL);

        //prepare to get request token
        $sig_method = new W3tcOAuthSignatureMethod_HMAC_SHA1();
        $parsed = parse_url($this->request_token_url);
        $params = array('callback' => $this->base_url);

        if (isset($parsed['query']))
            parse_str($parsed['query'], $params);

        $req_req = W3tcOAuthRequest::from_consumer_and_token($test_consumer, NULL, "GET", W3TC_CDN_OAUTH_REQUEST_TOKEN_URL, $params);
        $req_req->sign_request($sig_method, $test_consumer, NULL);

        $page = wp_remote_get($req_req->to_url());

        if (is_wp_error($page))
            $this->handle_error($page);

        parse_str ($page['body'],$tokens);

        return $tokens;
    }

    /**
     * Tokens are retrived using get_auth_request_tokens
     *
     * @param $oauth_token
     * @param $oauth_token_secret
     * @return string
     */
    private function _get_authorize_url($oauth_token, $oauth_token_secret){
        $callback_url = $this->base_url . "admin.php?page=w3tc_cdn&w3tc_cdn_oauth_access&key=$this->key&token=$oauth_token&token_secret=$oauth_token_secret&type=$this->cdn&_wpnonce=" .wp_create_nonce('w3tc'). "&endpoint="
            . urlencode($this->authorize_url);

        $auth_url = $this->authorize_url . "/?oauth_token=$oauth_token&oauth_callback=".urlencode($callback_url);

        return $auth_url;
    }

    /**
     * Redirects to authorization url
     */
    public function authorize(){
        $tokens = $this->_get_auth_request_tokens();
	$oauth_token = $tokens['oauth_token'];
        $oauth_token_secret = $tokens['oauth_token_secret'];
        $auth_url = $this->_get_authorize_url($oauth_token, $oauth_token_secret);

        w3_redirect($auth_url);
    }

    /**
     * @abstract
     * Returns the API credentials for the CDN
     *
     * @return array
     */
    abstract function get_api_keys();

    /**
     * @abstract
     * Prints the required javascript for extracting API Credentials from page.
     */
    abstract function print_javascript();

    /**
     * Prints error message when the authentication process encounters issue.
     * Also exits application.
     *
     * @param $page
     */
    public function handle_error($page){
        $messages=$page->errors['http_request_failed'];
        wp_die('<p>Authentication process encountered a problem.</p>'
            . '<p><strong>Details:</strong><br />'.implode('<br />',$messages).'</p>');
        exit;
    }
}
