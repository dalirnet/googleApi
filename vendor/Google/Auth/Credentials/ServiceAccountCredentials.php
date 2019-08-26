<?php

namespace Google\Auth\Credentials;

use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

class ServiceAccountCredentials extends CredentialsLoader
{
    protected $auth;

    public function __construct($scope, $jsonKey, $sub = null)
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
        $this->auth = new OAuth2(['audience' => self::TOKEN_CREDENTIAL_URI, 'issuer' => $jsonKey['client_email'], 'scope' => $scope, 'signingAlgorithm' => 'RS256', 'signingKey' => $jsonKey['private_key'], 'sub' => $sub, 'tokenCredentialUri' => self::TOKEN_CREDENTIAL_URI,]);
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        return $this->auth->fetchAuthToken($httpHandler);
    }

    public function getCacheKey()
    {
        $key = $this->auth->getIssuer() . ':' . $this->auth->getCacheKey();
        if ($sub = $this->auth->getSub()) {
            $key .= ':' . $sub;
        }
        return $key;
    }

    public function getLastReceivedToken()
    {
        return $this->auth->getLastReceivedToken();
    }

    public function updateMetadata($metadata, $authUri = null, callable $httpHandler = null)
    {
        $scope = $this->auth->getScope();
        if (!is_null($scope)) {
            return parent::updateMetadata($metadata, $authUri, $httpHandler);
        }
        $credJson = array('private_key' => $this->auth->getSigningKey(), 'client_email' => $this->auth->getIssuer(),);
        $jwtCreds = new ServiceAccountJwtAccessCredentials($credJson);
        return $jwtCreds->updateMetadata($metadata, $authUri, $httpHandler);
    }

    public function setSub($sub)
    {
        $this->auth->setSub($sub);
    }
}
