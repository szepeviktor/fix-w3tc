<?php

class OAuthService{

    /**
     * Creates CDN specific OAuth clients. Currently support NetDNA and MaxCDN.
     * @static
     * @param $type
     * @return OAuthClientBase
     * @throws Exception
     */
    public static function get_oauth_client($type){
        require_once W3TC_LIB_OAUTH_DIR . '/OAuthBase.php';
        require_once W3TC_LIB_OAUTH_DIR . '/OAuthClientBase.php';
        switch($type){
            default:
                throw new Exception('The provided CDN "' . $type . '" is not supported.');
        }
    }
}