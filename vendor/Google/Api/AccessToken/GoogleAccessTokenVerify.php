<?php

namespace Google\Api\AccessToken;

use Firebase\JWT\ExpiredException as ExpiredExceptionV3;
use Google\Api\GoogleException;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use Psr\Cache\CacheItemPoolInterface;
use Stash\Driver\FileSystem;
use Stash\Pool;

class GoogleAccessTokenVerify
{
    const FEDERATED_SIGNON_CERT_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    const OAUTH2_ISSUER = 'accounts.google.com';
    const OAUTH2_ISSUER_HTTPS = 'https://accounts.google.com';
    private $http;
    private $cache;

    public function __construct(ClientInterface $http = null, CacheItemPoolInterface $cache = null)
    {
        if (is_null($http)) {
            $http = new Client();
        }
        if (is_null($cache) && class_exists('Stash\Pool')) {
            $cache = new Pool(new FileSystem);
        }
        $this->http = $http;
        $this->cache = $cache;
        $this->jwt = $this->getJwtService();
    }

    private function getJwtService()
    {
        $jwtClass = 'JWT';
        if (class_exists('\Firebase\JWT\JWT')) {
            $jwtClass = 'Firebase\JWT\JWT';
        }
        if (property_exists($jwtClass, 'leeway')) {

            $jwtClass::$leeway = 1;
        }
        return new $jwtClass;
    }

    public function verifyIdToken($idToken, $audience = null)
    {
        if (empty($idToken)) {
            throw new LogicException('id_token cannot be null');
        }
        $this->setPhpsecConstants();
        $certs = $this->getFederatedSignOnCerts();
        foreach ($certs as $cert) {
            $modulus = new BigInteger($this->jwt->urlsafeB64Decode($cert['n']), 256);
            $exponent = new BigInteger($this->jwt->urlsafeB64Decode($cert['e']), 256);
            $rsa = new RSA();
            $rsa->loadKey(array('n' => $modulus, 'e' => $exponent));
            try {
                $payload = $this->jwt->decode($idToken, $rsa->getPublicKey(), array('RS256'));
                if (property_exists($payload, 'aud')) {
                    if ($audience && $payload->aud != $audience) {
                        return false;
                    }
                }
                $issuers = array(self::OAUTH2_ISSUER, self::OAUTH2_ISSUER_HTTPS);
                if (!isset($payload->iss) || !in_array($payload->iss, $issuers)) {
                    return false;
                }
                return (array)$payload;
            } catch (ExpiredException $e) {
                return false;
            } catch (ExpiredExceptionV3 $e) {
                return false;
            } catch (DomainException $e) {

            }
        }
        return false;
    }

    private function setPhpsecConstants()
    {
        if (filter_var(getenv('GAE_VM'), FILTER_VALIDATE_BOOLEAN)) {
            if (!defined('MATH_BIGINTEGER_OPENSSL_ENABLED')) {
                define('MATH_BIGINTEGER_OPENSSL_ENABLED', true);
            }
            if (!defined('CRYPT_RSA_MODE')) {
                define('CRYPT_RSA_MODE', RSA::MODE_OPENSSL);
            }
        }
    }

    private function getFederatedSignOnCerts()
    {
        $certs = null;
        if ($cache = $this->getCache()) {
            $cacheItem = $cache->getItem('federated_signon_certs_v3', 3600);
            $certs = $cacheItem->get();
        }
        if (!$certs) {
            $certs = $this->retrieveCertsFromLocation(self::FEDERATED_SIGNON_CERT_URL);
            if ($cache) {
                $cacheItem->set($certs);
                $cache->save($cacheItem);
            }
        }
        if (!isset($certs['keys'])) {
            throw new InvalidArgumentException('federated sign-on certs expects "keys" to be set');
        }
        return $certs['keys'];
    }

    private function getCache()
    {
        return $this->cache;
    }

    private function retrieveCertsFromLocation($url)
    {
        if (0 !== strpos($url, 'http')) {
            if (!$file = file_get_contents($url)) {
                throw new GoogleException("Failed to retrieve verification certificates: '" . $url . "'.");
            }
            return json_decode($file, true);
        }
        $response = $this->http->get($url);
        if ($response->getStatusCode() == 200) {
            return json_decode((string)$response->getBody(), true);
        }
        throw new GoogleException(sprintf('Failed to retrieve verification certificates: "%s".', $response->getBody()->getContents()), $response->getStatusCode());
    }
}
