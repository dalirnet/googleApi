<?php

namespace Google\Auth\Credentials;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

class ServiceAccountJwtAccessCredentials extends CredentialsLoader
{
    protected $auth;

    public function __construct($jsonKey)
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
        if (!array_key_exists('client_email', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the client_email field');
        }
        if (!array_key_exists('private_key', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the private_key field');
        }
        $this->auth = new OAuth2(['issuer' => $jsonKey['client_email'], 'sub' => $jsonKey['client_email'], 'signingAlgorithm' => 'RS256', 'signingKey' => $jsonKey['private_key'],]);
    }

    public function updateMetadata($metadata, $authUri = null, callable $httpHandler = null)
    {
        if (empty($authUri)) {
            return $metadata;
        }
        $this->auth->setAudience($authUri);
        return parent::updateMetadata($metadata, $authUri, $httpHandler);
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        $audience = $this->auth->getAudience();
        if (empty($audience)) {
            return null;
        }
        $access_token = $this->auth->toJwt();
        return array('access_token' => $access_token);
    }

    public function getCacheKey()
    {
        return $this->auth->getCacheKey();
    }

    public function getLastReceivedToken()
    {
        return $this->auth->getLastReceivedToken();
    }
}
