<?php

namespace Google\Auth\Credentials;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

class UserRefreshCredentials extends CredentialsLoader
{
    protected $auth;

    public function __construct($scope, $jsonKey)
    {
        if (is_string($jsonKey)) {
            if (!file_exists($jsonKey)) {
                throw new \InvalidArgumentException('file does not exist');
            }
            $jsonKeyStream = file_get_contents($jsonKey);
            if (!$jsonKey = json_decode($jsonKeyStream, true)) {
                throw new \LogicException('invalid json for auth config');
            }
        }
        if (!array_key_exists('client_id', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the client_id field');
        }
        if (!array_key_exists('client_secret', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the client_secret field');
        }
        if (!array_key_exists('refresh_token', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the refresh_token field');
        }
        $this->auth = new OAuth2(['clientId' => $jsonKey['client_id'], 'clientSecret' => $jsonKey['client_secret'], 'refresh_token' => $jsonKey['refresh_token'], 'scope' => $scope, 'tokenCredentialUri' => self::TOKEN_CREDENTIAL_URI,]);
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        return $this->auth->fetchAuthToken($httpHandler);
    }

    public function getCacheKey()
    {
        return $this->auth->getClientId() . ':' . $this->auth->getCacheKey();
    }

    public function getLastReceivedToken()
    {
        return $this->auth->getLastReceivedToken();
    }
}
