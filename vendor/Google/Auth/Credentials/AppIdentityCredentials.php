<?php

namespace Google\Auth\Credentials;

use Google\Auth\CredentialsLoader;

class AppIdentityCredentials extends CredentialsLoader
{
    const cacheKey = 'GOOGLE_AUTH_PHP_APPIDENTITY';
    protected $lastReceivedToken;
    private $scope;

    public function __construct($scope = array())
    {
        $this->scope = $scope;
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        if (!self::onAppEngine()) {
            return array();
        }
    }

    public static function onAppEngine()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine') !== false;
    }

    public function getLastReceivedToken()
    {
        if ($this->lastReceivedToken) {
            return ['access_token' => $this->lastReceivedToken['access_token'], 'expires_at' => $this->lastReceivedToken['expiration_time'],];
        }
        return null;
    }

    public function getCacheKey()
    {
        return self::cacheKey;
    }
}
