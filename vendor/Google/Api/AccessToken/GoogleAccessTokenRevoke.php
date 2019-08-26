<?php

namespace Google\Api\AccessToken;

use Google\Api\GoogleClient;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;

class GoogleAccessTokenRevoke
{
    private $http;

    public function __construct(ClientInterface $http = null)
    {
        $this->http = $http;
    }

    public function revokeToken(array $token)
    {
        if (isset($token['refresh_token'])) {
            $tokenString = $token['refresh_token'];
        } else {
            $tokenString = $token['access_token'];
        }
        $body = Psr7\stream_for(http_build_query(array('token' => $tokenString)));
        $request = new Request('POST', GoogleClient::OAUTH2_REVOKE_URI, ['Cache-Control' => 'no-store', 'Content-Type' => 'application/x-www-form-urlencoded',], $body);
        $httpHandler = HttpHandlerFactory::build($this->http);
        $response = $httpHandler($request);
        if ($response->getStatusCode() == 200) {
            return true;
        }
        return false;
    }
}
