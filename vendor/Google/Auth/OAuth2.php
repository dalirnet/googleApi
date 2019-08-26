<?php

namespace Google\Auth;

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Guzzle\Psr7;
use Guzzle\Psr7\Request;
use InvalidArgumentException;
use Psr\HttpMessage\ResponseInterface;

class OAuth2 implements FetchAuthTokenInterface
{
    const DEFAULT_EXPIRY_SECONDS = 3600;
    const DEFAULT_SKEW_SECONDS = 60;
    const JWT_URN = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    public static $knownSigningAlgorithms = array('HS256', 'HS512', 'HS384', 'RS256',);
    public static $knownGrantTypes = array('authorization_code', 'refresh_token', 'password', 'client_credentials',);
    private $authorizationUri;
    private $tokenCredentialUri;
    private $redirectUri;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $scope;
    private $state;
    private $code;
    private $issuer;
    private $audience;
    private $sub;
    private $expiry;
    private $signingKey;
    private $signingAlgorithm;
    private $refreshToken;
    private $accessToken;
    private $idToken;
    private $expiresIn;
    private $expiresAt;
    private $issuedAt;
    private $grantType;
    private $extensionParams;

    public function __construct(array $config)
    {
        $opts = array_merge(['expiry' => self::DEFAULT_EXPIRY_SECONDS, 'extensionParams' => [], 'authorizationUri' => null, 'redirectUri' => null, 'tokenCredentialUri' => null, 'state' => null, 'username' => null, 'password' => null, 'clientId' => null, 'clientSecret' => null, 'issuer' => null, 'sub' => null, 'audience' => null, 'signingKey' => null, 'signingAlgorithm' => null, 'scope' => null,], $config);
        $this->setAuthorizationUri($opts['authorizationUri']);
        $this->setRedirectUri($opts['redirectUri']);
        $this->setTokenCredentialUri($opts['tokenCredentialUri']);
        $this->setState($opts['state']);
        $this->setUsername($opts['username']);
        $this->setPassword($opts['password']);
        $this->setClientId($opts['clientId']);
        $this->setClientSecret($opts['clientSecret']);
        $this->setIssuer($opts['issuer']);
        $this->setSub($opts['sub']);
        $this->setExpiry($opts['expiry']);
        $this->setAudience($opts['audience']);
        $this->setSigningKey($opts['signingKey']);
        $this->setSigningAlgorithm($opts['signingAlgorithm']);
        $this->setScope($opts['scope']);
        $this->setExtensionParams($opts['extensionParams']);
        $this->updateToken($opts);
    }

    public function updateToken(array $config)
    {
        $opts = array_merge(['extensionParams' => [], 'refresh_token' => null, 'access_token' => null, 'id_token' => null, 'expires' => null, 'expires_in' => null, 'expires_at' => null, 'issued_at' => null,], $config);
        $this->setExpiresAt($opts['expires']);
        $this->setExpiresAt($opts['expires_at']);
        $this->setExpiresIn($opts['expires_in']);
        if (!is_null($opts['issued_at'])) {
            $this->setIssuedAt($opts['issued_at']);
        }
        $this->setAccessToken($opts['access_token']);
        $this->setIdToken($opts['id_token']);
        $this->setRefreshToken($opts['refresh_token']);
    }

    public function verifyIdToken($publicKey = null, $allowed_algs = array())
    {
        $idToken = $this->getIdToken();
        if (is_null($idToken)) {
            return null;
        }
        $resp = $this->jwtDecode($idToken, $publicKey, $allowed_algs);
        if (!property_exists($resp, 'aud')) {
            throw new \DomainException('No audience found the id token');
        }
        if ($resp->aud != $this->getAudience()) {
            throw new \DomainException('Wrong audience present in the id token');
        }
        return $resp;
    }

    public function getIdToken()
    {
        return $this->idToken;
    }

    public function setIdToken($idToken)
    {
        $this->idToken = $idToken;
    }

    private function jwtDecode($idToken, $publicKey, $allowedAlgs)
    {
        if (class_exists('Firebase\JWT\JWT')) {
            return \Firebase\JWT\JWT::decode($idToken, $publicKey, $allowedAlgs);
        }
        return \JWT::decode($idToken, $publicKey, $allowedAlgs);
    }

    public function getAudience()
    {
        return $this->audience;
    }

    public function setAudience($audience)
    {
        $this->audience = $audience;
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        if (is_null($httpHandler)) {
            $httpHandler = HttpHandlerFactory::build();
        }
        $response = $httpHandler($this->generateCredentialsRequest());
        $credentials = $this->parseTokenResponse($response);
        $this->updateToken($credentials);
        return $credentials;
    }

    public function generateCredentialsRequest()
    {
        $uri = $this->getTokenCredentialUri();
        if (is_null($uri)) {
            throw new \DomainException('No token credential URI was set.');
        }
        $grantType = $this->getGrantType();
        $params = array('grant_type' => $grantType);
        switch ($grantType) {
            case 'authorization_code':
                $params['code'] = $this->getCode();
                $params['redirect_uri'] = $this->getRedirectUri();
                $this->addClientCredentials($params);
                break;
            case 'password':
                $params['username'] = $this->getUsername();
                $params['password'] = $this->getPassword();
                $this->addClientCredentials($params);
                break;
            case 'refresh_token':
                $params['refresh_token'] = $this->getRefreshToken();
                $this->addClientCredentials($params);
                break;
            case self::JWT_URN:
                $params['assertion'] = $this->toJwt();
                break;
            default:
                if (!is_null($this->getRedirectUri())) {

                    throw new \DomainException('Missing authorization code');
                }
                unset($params['grant_type']);
                if (!is_null($grantType)) {
                    $params['grant_type'] = $grantType;
                }
                $params = array_merge($params, $this->getExtensionParams());
        }
        $headers = ['Cache-Control' => 'no-store', 'Content-Type' => 'application/x-www-form-urlencoded',];
        return new Request('POST', $uri, $headers, Psr7\build_query($params));
    }

    public function getTokenCredentialUri()
    {
        return $this->tokenCredentialUri;
    }

    public function setTokenCredentialUri($uri)
    {
        $this->tokenCredentialUri = $this->coerceUri($uri);
    }

    public function getGrantType()
    {
        if (!is_null($this->grantType)) {
            return $this->grantType;
        }
        if (!is_null($this->code)) {
            return 'authorization_code';
        } elseif (!is_null($this->refreshToken)) {
            return 'refresh_token';
        } elseif (!is_null($this->username) && !is_null($this->password)) {
            return 'password';
        } elseif (!is_null($this->issuer) && !is_null($this->signingKey)) {
            return self::JWT_URN;
        } else {
            return null;
        }
    }

    public function setGrantType($grantType)
    {
        if (in_array($grantType, self::$knownGrantTypes)) {
            $this->grantType = $grantType;
        } else {

            if (!$this->isAbsoluteUri($grantType)) {
                throw new InvalidArgumentException('invalid grant type');
            }
            $this->grantType = (string)$grantType;
        }
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function setRedirectUri($uri)
    {
        if (is_null($uri)) {
            $this->redirectUri = null;
            return;
        }
        if (!$this->isAbsoluteUri($uri)) {

            if ('postmessage' !== (string)$uri) {
                throw new InvalidArgumentException('Redirect URI must be absolute');
            }
        }
        $this->redirectUri = (string)$uri;
    }

    private function addClientCredentials(&$params)
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        if ($clientId && $clientSecret) {
            $params['client_id'] = $clientId;
            $params['client_secret'] = $clientSecret;
        }
        return $params;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function toJwt(array $config = [])
    {
        if (is_null($this->getSigningKey())) {
            throw new \DomainException('No signing key available');
        }
        if (is_null($this->getSigningAlgorithm())) {
            throw new \DomainException('No signing algorithm specified');
        }
        $now = time();
        $opts = array_merge(['skew' => self::DEFAULT_SKEW_SECONDS,], $config);
        $assertion = ['iss' => $this->getIssuer(), 'aud' => $this->getAudience(), 'exp' => ($now + $this->getExpiry()), 'iat' => ($now - $opts['skew']),];
        foreach ($assertion as $k => $v) {
            if (is_null($v)) {
                throw new \DomainException($k . ' should not be null');
            }
        }
        if (!(is_null($this->getScope()))) {
            $assertion['scope'] = $this->getScope();
        }
        if (!(is_null($this->getSub()))) {
            $assertion['sub'] = $this->getSub();
        }
        return $this->jwtEncode($assertion, $this->getSigningKey(), $this->getSigningAlgorithm());
    }

    public function getSigningKey()
    {
        return $this->signingKey;
    }

    public function setSigningKey($signingKey)
    {
        $this->signingKey = $signingKey;
    }

    public function getSigningAlgorithm()
    {
        return $this->signingAlgorithm;
    }

    public function setSigningAlgorithm($signingAlgorithm)
    {
        if (is_null($signingAlgorithm)) {
            $this->signingAlgorithm = null;
        } elseif (!in_array($signingAlgorithm, self::$knownSigningAlgorithms)) {
            throw new InvalidArgumentException('unknown signing algorithm');
        } else {
            $this->signingAlgorithm = $signingAlgorithm;
        }
    }

    public function getIssuer()
    {
        return $this->issuer;
    }

    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    public function getExpiry()
    {
        return $this->expiry;
    }

    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;
    }

    public function getScope()
    {
        if (is_null($this->scope)) {
            return $this->scope;
        }
        return implode(' ', $this->scope);
    }

    public function setScope($scope)
    {
        if (is_null($scope)) {
            $this->scope = null;
        } elseif (is_string($scope)) {
            $this->scope = explode(' ', $scope);
        } elseif (is_array($scope)) {
            foreach ($scope as $s) {
                $pos = strpos($s, ' ');
                if ($pos !== false) {
                    throw new InvalidArgumentException('array scope values should not contain spaces');
                }
            }
            $this->scope = $scope;
        } else {
            throw new InvalidArgumentException('scopes should be a string or array of strings');
        }
    }

    public function getSub()
    {
        return $this->sub;
    }

    public function setSub($sub)
    {
        $this->sub = $sub;
    }

    private function jwtEncode($assertion, $signingKey, $signingAlgorithm)
    {
        if (class_exists('Firebase\JWT\JWT')) {
            return \Firebase\JWT\JWT::encode($assertion, $signingKey, $signingAlgorithm);
        }
        return \JWT::encode($assertion, $signingKey, $signingAlgorithm);
    }

    public function getExtensionParams()
    {
        return $this->extensionParams;
    }

    public function setExtensionParams($extensionParams)
    {
        $this->extensionParams = $extensionParams;
    }

    public function parseTokenResponse(ResponseInterface $resp)
    {
        $body = (string)$resp->getBody();
        if ($resp->hasHeader('Content-Type') && $resp->getHeaderLine('Content-Type') == 'application/x-www-form-urlencoded') {
            $res = array();
            parse_str($body, $res);
            return $res;
        } else {

            if (null === $res = json_decode($body, true)) {
                throw new \Exception('Invalid JSON response');
            }
            return $res;
        }
    }

    private function isAbsoluteUri($uri)
    {
        $uri = $this->coerceUri($uri);
        return $uri->getScheme() && ($uri->getHost() || $uri->getPath());
    }

    private function coerceUri($uri)
    {
        if (is_null($uri)) {
            return;
        }
        return Psr7\uri_for($uri);
    }

    public function getCacheKey()
    {
        if (is_string($this->scope)) {
            return $this->scope;
        } elseif (is_array($this->scope)) {
            return implode(':', $this->scope);
        }
        return null;
    }

    public function buildFullAuthorizationUri(array $config = [])
    {
        if (is_null($this->getAuthorizationUri())) {
            throw new InvalidArgumentException('requires an authorizationUri to have been set');
        }
        $params = array_merge(['response_type' => 'code', 'access_type' => 'offline', 'client_id' => $this->clientId, 'redirect_uri' => $this->redirectUri, 'state' => $this->state, 'scope' => $this->getScope(),], $config);
        if (is_null($params['client_id'])) {
            throw new InvalidArgumentException('missing the required client identifier');
        }
        if (is_null($params['redirect_uri'])) {
            throw new InvalidArgumentException('missing the required redirect URI');
        }
        if (!empty($params['prompt']) && !empty($params['approval_prompt'])) {
            throw new InvalidArgumentException('prompt and approval_prompt are mutually exclusive');
        }
        $result = clone $this->authorizationUri;
        $existingParams = Psr7\parse_query($result->getQuery());
        $result = $result->withQuery(Psr7\build_query(array_merge($existingParams, $params)));
        if ($result->getScheme() != 'https') {
            throw new InvalidArgumentException('Authorization endpoint must be protected by TLS');
        }
        return $result;
    }

    public function getAuthorizationUri()
    {
        return $this->authorizationUri;
    }

    public function setAuthorizationUri($uri)
    {
        $this->authorizationUri = $this->coerceUri($uri);
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    public function setExpiresIn($expiresIn)
    {
        if (is_null($expiresIn)) {
            $this->expiresIn = null;
            $this->issuedAt = null;
        } else {
            $this->issuedAt = time();
            $this->expiresIn = (int)$expiresIn;
        }
    }

    public function isExpired()
    {
        $expiration = $this->getExpiresAt();
        $now = time();
        return !is_null($expiration) && $now >= $expiration;
    }

    public function getExpiresAt()
    {
        if (!is_null($this->expiresAt)) {
            return $this->expiresAt;
        } elseif (!is_null($this->issuedAt) && !is_null($this->expiresIn)) {
            return $this->issuedAt + $this->expiresIn;
        }
        return null;
    }

    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }

    public function getIssuedAt()
    {
        return $this->issuedAt;
    }

    public function setIssuedAt($issuedAt)
    {
        $this->issuedAt = $issuedAt;
    }

    public function getLastReceivedToken()
    {
        if ($token = $this->getAccessToken()) {
            return ['access_token' => $token, 'expires_at' => $this->getExpiresAt(),];
        }
        return null;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
}
