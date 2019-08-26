<?php

namespace Google\Api\AuthHandler;

use Google\Auth\CredentialsLoader;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Auth\Middleware\AuthTokenMiddleware;
use Google\Auth\Middleware\ScopedAccessTokenMiddleware;
use Google\Auth\Middleware\SimpleMiddleware;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;

class GoogleAuthHandlerGuzzle6AuthHandler
{
    protected $cache;
    protected $cacheConfig;

    public function __construct(CacheItemPoolInterface $cache = null, array $cacheConfig = [])
    {
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }

    public function attachCredentials(ClientInterface $http, CredentialsLoader $credentials, callable $tokenCallback = null)
    {
        $authHttp = $this->createAuthHttp($http);
        $authHttpHandler = HttpHandlerFactory::build($authHttp);
        $middleware = new AuthTokenMiddleware($credentials, $this->cacheConfig, $this->cache, $authHttpHandler, $tokenCallback);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'google_auth';
        $http = new Client($config);
        return $http;
    }

    private function createAuthHttp(ClientInterface $http)
    {
        return new Client(['base_uri' => $http->getConfig('base_uri'), 'exceptions' => true, 'verify' => $http->getConfig('verify'), 'proxy' => $http->getConfig('proxy'),]);
    }

    public function attachToken(ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use ($token) {
            return $token['access_token'];
        };
        $middleware = new ScopedAccessTokenMiddleware($tokenFunc, $scopes, [], $this->cache);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'scoped';
        $http = new Client($config);
        return $http;
    }

    public function attachKey(ClientInterface $http, $key)
    {
        $middleware = new SimpleMiddleware(['key' => $key]);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'simple';
        $http = new Client($config);
        return $http;
    }
}
