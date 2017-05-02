<?php
switch($cdn){
    case 'maxcdn':
        define('W3TC_CDN_OAUTH_KEY','9a89c45125b029d24b0f10f49f4741f604e6751b3');
        define('W3TC_CDN_OAUTH_SECRET','8bd80f08145acae93eae03ea1474f361');
        define('W3TC_CDN_OAUTH_REQUEST_TOKEN_URL','http://login.maxcdn.com/oauth/requesttoken');
        define('W3TC_CDN_OAUTH_AUTHORIZE_URL','http://login.maxcdn.com/oauth/authorize');
        define('W3TC_CDN_OAUTH_ACCESS_TOKEN','http://login.maxcdn.com/oauth/accesstoken');
        define('W3TC_CDN_OAUTH_CREATE_API_USER','http://login.maxcdn.com/oauth/createapiuser');
        break;

    case 'netdna':
        define('W3TC_CDN_OAUTH_KEY','9a89c45125b029d24b0f10f49f4741f604e6751b3');
        define('W3TC_CDN_OAUTH_SECRET','8bd80f08145acae93eae03ea1474f361');
        define('W3TC_CDN_OAUTH_REQUEST_TOKEN_URL','http://login.netdna.com/oauth/requesttoken');
        define('W3TC_CDN_OAUTH_AUTHORIZE_URL','http://login.netdna.com/oauth/authorize');
        define('W3TC_CDN_OAUTH_ACCESS_TOKEN','http://login.netdna.com/oauth/accesstoken');
        define('W3TC_CDN_OAUTH_CREATE_API_USER','http://login.netdna.com/oauth/createapiuser');
        break;
}