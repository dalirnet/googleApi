<?php

namespace Google\Api;

use Google\Api\AuthHandler\GoogleAuthHandlerAuthHandlerFactory;
use Google\Api\Http\GoogleHttpREST;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Auth\CredentialsLoader;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Auth\OAuth2;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Ring\Client\StreamHandler;
use Guzzle\Psr7;
use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\HttpMessage\RequestInterface;
use Psr\Log\LoggerInterface;

class GoogleClient
{
    const LIBVER = "2.0.0-alpha";
    const USER_AGENT_SUFFIX = "google-api-php-client/";
    const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
    const OAUTH2_TOKEN_URI = 'https://www.googleapis.com/oauth2/v4/token';
    const OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
    const API_BASE_PATH = 'https://www.googleapis.com';
    protected $requestedScopes = [];
    private $auth;
    private $http;
    private $cache;
    private $token;
    private $config;
    private $logger;
    private $deferExecution = false;

    public function __construct($config = array())
    {
        $this->config = array_merge(['application_name' => '', 'base_path' => self::API_BASE_PATH, 'client_id' => '', 'client_secret' => '', 'redirect_uri' => null, 'state' => null, 'developer_key' => '', 'use_application_default_credentials' => false, 'signing_key' => null, 'signing_algorithm' => null, 'subject' => null, 'hd' => '', 'prompt' => '', 'openid.realm' => '', 'include_granted_scopes' => null, 'login_hint' => '', 'request_visible_actions' => '', 'access_type' => 'online', 'approval_prompt' => 'auto', 'retry' => array(), 'cache_config' => [], 'token_callback' => null,], $config);
    }

    public function authenticate($code)
    {
        return $this->fetchAccessTokenWithAuthCode($code);
    }

    public function fetchAccessTokenWithAuthCode($code)
    {
        if (strlen($code) == 0) {
            throw new InvalidArgumentException("Invalid code");
        }
        $auth = $this->getOAuth2Service();
        $auth->setCode($code);
        $auth->setRedirectUri($this->getRedirectUri());
        $httpHandler = HttpHandlerFactory::build($this->getHttpClient());
        $creds = $auth->fetchAuthToken($httpHandler);
        if ($creds && isset($creds['access_token'])) {
            $creds['created'] = time();
            $this->setAccessToken($creds);
        }
        return $creds;
    }

    public function getOAuth2Service()
    {
        if (!isset($this->auth)) {
            $this->auth = $this->createOAuth2Service();
        }
        return $this->auth;
    }

    protected function createOAuth2Service()
    {
        $auth = new OAuth2(['clientId' => $this->getClientId(), 'clientSecret' => $this->getClientSecret(), 'authorizationUri' => self::OAUTH2_AUTH_URL, 'tokenCredentialUri' => self::OAUTH2_TOKEN_URI, 'redirectUri' => $this->getRedirectUri(), 'issuer' => $this->config['client_id'], 'signingKey' => $this->config['signing_key'], 'signingAlgorithm' => $this->config['signing_algorithm'],]);
        return $auth;
    }

    public function getClientId()
    {
        return $this->config['client_id'];
    }

    public function getClientSecret()
    {
        return $this->config['client_secret'];
    }

    public function getRedirectUri()
    {
        return $this->config['redirect_uri'];
    }

    public function getHttpClient()
    {
        if (is_null($this->http)) {
            $this->http = $this->createDefaultHttpClient();
        }
        return $this->http;
    }

    protected function createDefaultHttpClient()
    {
        $options = ['exceptions' => false];
        $version = ClientInterface::VERSION;
        if ('5' === $version[0]) {
            $options = ['base_url' => $this->config['base_path'], 'defaults' => $options,];
            if ($this->isAppEngine()) {

                $options['handler'] = new StreamHandler();
                $options['defaults']['verify'] = '/etc/ca-certificates.crt';
            }
        } else {

            $options['base_uri'] = $this->config['base_path'];
        }
        return new Client($options);
    }

    public function isAppEngine()
    {
        return (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Google App Engine') !== false);
    }

    public function setAccessToken($token)
    {
        if (is_string($token)) {
            if ($json = json_decode($token, true)) {
                $token = $json;
            } else {

                $token = array('access_token' => $token,);
            }
        }
        if ($token == null) {
            throw new InvalidArgumentException('invalid json token');
        }
        if (!isset($token['access_token'])) {
            throw new InvalidArgumentException("Invalid token format");
        }
        $this->token = $token;
    }

    public function refreshTokenWithAssertion()
    {
        return $this->fetchAccessTokenWithAssertion();
    }

    public function fetchAccessTokenWithAssertion(ClientInterface $authHttp = null)
    {
        if (!$this->isUsingApplicationDefaultCredentials()) {
            throw new DomainException('set the JSON service account credentials using' . ' Google_Client::setAuthConfig or set the path to your JSON file' . ' with the "GOOGLE_APPLICATION_CREDENTIALS" environment variable' . ' and call Google_Client::useApplicationDefaultCredentials to' . ' refresh a token with assertion.');
        }
        $this->getLogger()->log('info', 'OAuth2 access token refresh with Signed JWT assertion grants.');
        $credentials = $this->createApplicationDefaultCredentials();
        $httpHandler = HttpHandlerFactory::build($authHttp);
        $accessToken = $credentials->fetchAuthToken($httpHandler);
        if ($accessToken && isset($accessToken['access_token'])) {
            $this->setAccessToken($accessToken);
        }
        return $accessToken;
    }

    public function isUsingApplicationDefaultCredentials()
    {
        return $this->config['use_application_default_credentials'];
    }

    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = $this->createDefaultLogger();
        }
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function createDefaultLogger()
    {
        $logger = new Logger('google-api-php-client');
        $logger->pushHandler(new MonologStreamHandler('php://stderr', Logger::NOTICE));
        return $logger;
    }

    private function createApplicationDefaultCredentials()
    {
        $scopes = $this->prepareScopes();
        $sub = $this->config['subject'];
        $signingKey = $this->config['signing_key'];
        if ($signingKey) {
            $serviceAccountCredentials = array('client_id' => $this->config['client_id'], 'client_email' => $this->config['client_email'], 'private_key' => $signingKey, 'type' => 'service_account',);
            $keyStream = Psr7\stream_for(json_encode($serviceAccountCredentials));
            $credentials = CredentialsLoader::makeCredentials($scopes, $keyStream);
        } else {
            $credentials = ApplicationDefaultCredentials::getCredentials($scopes);
        }
        if ($sub) {
            if (!$credentials instanceof ServiceAccountCredentials) {
                throw new DomainException('domain-wide authority requires service account credentials');
            }
            $credentials->setSub($sub);
        }
        return $credentials;
    }

    public function prepareScopes()
    {
        if (empty($this->requestedScopes)) {
            return null;
        }
        $scopes = implode(' ', $this->requestedScopes);
        return $scopes;
    }

    public function refreshToken($refreshToken)
    {
        return $this->fetchAccessTokenWithRefreshToken($refreshToken);
    }

    public function fetchAccessTokenWithRefreshToken($refreshToken = null)
    {
        if (is_null($refreshToken)) {
            if (!isset($this->token['refresh_token'])) {
                throw new LogicException('refresh token must be passed in or set as part of setAccessToken');
            }
            $refreshToken = $this->token['refresh_token'];
        }
        $this->getLogger()->info('OAuth2 access token refresh');
        $auth = $this->getOAuth2Service();
        $auth->setRefreshToken($refreshToken);
        $httpHandler = HttpHandlerFactory::build($this->getHttpClient());
        $creds = $auth->fetchAuthToken($httpHandler);
        if ($creds && isset($creds['access_token'])) {
            $creds['created'] = time();
            $this->setAccessToken($creds);
        }
        return $creds;
    }

    public function createAuthUrl($scope = null)
    {
        if (empty($scope)) {
            $scope = $this->prepareScopes();
        }
        if (is_array($scope)) {
            $scope = implode(' ', $scope);
        }
        $approvalPrompt = $this->config['prompt'] ? null : $this->config['approval_prompt'];
        $includeGrantedScopes = $this->config['include_granted_scopes'] === null ? null : var_export($this->config['include_granted_scopes'], true);
        $params = array_filter(['access_type' => $this->config['access_type'], 'approval_prompt' => $approvalPrompt, 'hd' => $this->config['hd'], 'include_granted_scopes' => $includeGrantedScopes, 'login_hint' => $this->config['login_hint'], 'openid.realm' => $this->config['openid.realm'], 'prompt' => $this->config['prompt'], 'response_type' => 'code', 'scope' => $scope, 'state' => $this->config['state'],]);
        $rva = $this->config['request_visible_actions'];
        if (strlen($rva) > 0 && false !== strpos($scope, 'plus.login')) {
            $params['request_visible_actions'] = $rva;
        }
        $auth = $this->getOAuth2Service();
        return (string)$auth->buildFullAuthorizationUri($params);
    }

    public function getRefreshToken()
    {
        if (isset($this->token['refresh_token'])) {
            return $this->token['refresh_token'];
        }
    }

    public function getAuth()
    {
        throw new BadMethodCallException('This function no longer exists. See UPGRADING.md for more information');
    }

    public function setAuth($auth)
    {
        throw new BadMethodCallException('This function no longer exists. See UPGRADING.md for more information');
    }

    public function setState($state)
    {
        $this->config['state'] = $state;
    }

    public function setAccessType($accessType)
    {
        $this->config['access_type'] = $accessType;
    }

    public function setApprovalPrompt($approvalPrompt)
    {
        $this->config['approval_prompt'] = $approvalPrompt;
    }

    public function setLoginHint($loginHint)
    {
        $this->config['login_hint'] = $loginHint;
    }

    public function setApplicationName($applicationName)
    {
        $this->config['application_name'] = $applicationName;
    }

    public function setRequestVisibleActions($requestVisibleActions)
    {
        if (is_array($requestVisibleActions)) {
            $requestVisibleActions = join(" ", $requestVisibleActions);
        }
        $this->config['request_visible_actions'] = $requestVisibleActions;
    }

    public function setDeveloperKey($developerKey)
    {
        $this->config['developer_key'] = $developerKey;
    }

    public function setHostedDomain($hd)
    {
        $this->config['hd'] = $hd;
    }

    public function setPrompt($prompt)
    {
        $this->config['prompt'] = $prompt;
    }

    public function setOpenidRealm($realm)
    {
        $this->config['openid.realm'] = $realm;
    }

    public function setIncludeGrantedScopes($include)
    {
        $this->config['include_granted_scopes'] = $include;
    }

    public function setTokenCallback(callable $tokenCallback)
    {
        $this->config['token_callback'] = $tokenCallback;
    }

    public function revokeToken($token = null)
    {
        $tokenRevoker = new Google_AccessToken_Revoke($this->getHttpClient());
        return $tokenRevoker->revokeToken($token ?: $this->getAccessToken());
    }

    public function getAccessToken()
    {
        return $this->token;
    }

    public function verifyIdToken($idToken = null)
    {
        $tokenVerifier = new Google_AccessToken_Verify($this->getHttpClient(), $this->getCache());
        if (is_null($idToken)) {
            $token = $this->getAccessToken();
            if (!isset($token['id_token'])) {
                throw new LogicException('id_token must be passed in or set as part of setAccessToken');
            }
            $idToken = $token['id_token'];
        }
        return $tokenVerifier->verifyIdToken($idToken, $this->getClientId());
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setScopes($scopes)
    {
        $this->requestedScopes = array();
        $this->addScope($scopes);
    }

    public function addScope($scope_or_scopes)
    {
        if (is_string($scope_or_scopes) && !in_array($scope_or_scopes, $this->requestedScopes)) {
            $this->requestedScopes[] = $scope_or_scopes;
        } else if (is_array($scope_or_scopes)) {
            foreach ($scope_or_scopes as $scope) {
                $this->addScope($scope);
            }
        }
    }

    public function getScopes()
    {
        return $this->requestedScopes;
    }

    public function execute(RequestInterface $request, $expectedClass = null)
    {
        $request = $request->withHeader('User-Agent', $this->config['application_name'] . " " . self::USER_AGENT_SUFFIX . $this->getLibraryVersion());
        $http = $this->authorize();
        return GoogleHttpREST::execute($http, $request, $expectedClass, $this->config['retry']);
    }

    public function getLibraryVersion()
    {
        return self::LIBVER;
    }

    public function authorize(ClientInterface $http = null, ClientInterface $authHttp = null)
    {
        $credentials = null;
        $token = null;
        $scopes = null;
        if (is_null($http)) {
            $http = $this->getHttpClient();
        }
        if ($this->isUsingApplicationDefaultCredentials()) {
            $credentials = $this->createApplicationDefaultCredentials();
        } elseif ($token = $this->getAccessToken()) {
            $scopes = $this->prepareScopes();
            if ($this->isAccessTokenExpired() && isset($token['refresh_token'])) {
                $credentials = $this->createUserRefreshCredentials($scopes, $token['refresh_token']);
            }
        }
        $authHandler = $this->getAuthHandler();
        if ($credentials) {
            $callback = $this->config['token_callback'];
            $http = $authHandler->attachCredentials($http, $credentials, $callback);
        } elseif ($token) {
            $http = $authHandler->attachToken($http, $token, (array)$scopes);
        } elseif ($key = $this->config['developer_key']) {
            $http = $authHandler->attachKey($http, $key);
        }
        return $http;
    }

    public function isAccessTokenExpired()
    {
        if (!$this->token) {
            return true;
        }
        $created = 0;
        if (isset($this->token['created'])) {
            $created = $this->token['created'];
        } elseif (isset($this->token['id_token'])) {

            $idToken = $this->token['id_token'];
            if (substr_count($idToken, '.') == 2) {
                $parts = explode('.', $idToken);
                $payload = json_decode(base64_decode($parts[1]), true);
                if ($payload && isset($payload['iat'])) {
                    $created = $payload['iat'];
                }
            }
        }
        $expired = ($created + ($this->token['expires_in'] - 30)) < time();
        return $expired;
    }

    private function createUserRefreshCredentials($scope, $refreshToken)
    {
        $creds = array_filter(array('client_id' => $this->getClientId(), 'client_secret' => $this->getClientSecret(), 'refresh_token' => $refreshToken,));
        return new UserRefreshCredentials($scope, $creds);
    }

    protected function getAuthHandler()
    {
        return GoogleAuthHandlerAuthHandlerFactory::build($this->getCache(), $this->config['cache_config']);
    }

    public function setUseBatch($useBatch)
    {

        $this->setDefer($useBatch);
    }

    public function setDefer($defer)
    {
        $this->deferExecution = $defer;
    }

    public function getConfig($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
    }

    public function setAuthConfigFile($file)
    {
        $this->setAuthConfig($file);
    }

    public function setAuthConfig($config)
    {
        if (is_string($config)) {
            if (!file_exists($config)) {
                throw new InvalidArgumentException('file does not exist');
            }
            $json = file_get_contents($config);
            if (!$config = json_decode($json, true)) {
                throw new LogicException('invalid json for auth config');
            }
        }
        $key = isset($config['installed']) ? 'installed' : 'web';
        if (isset($config['type']) && $config['type'] == 'service_account') {

            $this->useApplicationDefaultCredentials();
            $this->setClientId($config['client_id']);
            $this->config['client_email'] = $config['client_email'];
            $this->config['signing_key'] = $config['private_key'];
            $this->config['signing_algorithm'] = 'HS256';
        } elseif (isset($config[$key])) {

            $this->setClientId($config[$key]['client_id']);
            $this->setClientSecret($config[$key]['client_secret']);
            if (isset($config[$key]['redirect_uris'])) {
                $this->setRedirectUri($config[$key]['redirect_uris'][0]);
            }
        } else {

            $this->setClientId($config['client_id']);
            $this->setClientSecret($config['client_secret']);
            if (isset($config['redirect_uris'])) {
                $this->setRedirectUri($config['redirect_uris'][0]);
            }
        }
    }

    public function useApplicationDefaultCredentials($useAppCreds = true)
    {
        $this->config['use_application_default_credentials'] = $useAppCreds;
    }

    public function setClientId($clientId)
    {
        $this->config['client_id'] = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
        $this->config['client_secret'] = $clientSecret;
    }

    public function setRedirectUri($redirectUri)
    {
        $this->config['redirect_uri'] = $redirectUri;
    }

    public function setSubject($subject)
    {
        $this->config['subject'] = $subject;
    }

    public function shouldDefer()
    {
        return $this->deferExecution;
    }

    public function setCacheConfig(array $cacheConfig)
    {
        $this->config['cache_config'] = $cacheConfig;
    }

    public function setHttpClient(ClientInterface $http)
    {
        $this->http = $http;
    }
}
