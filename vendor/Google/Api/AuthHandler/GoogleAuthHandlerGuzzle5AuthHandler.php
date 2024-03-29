<?php

namespace Google\Api\AuthHandler;

use Google\Auth\CredentialsLoader;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Auth\Subscriber\AuthTokenSubscriber;
use Google\Auth\Subscriber\ScopedAccessTokenSubscriber;
use Google\Auth\Subscriber\SimpleSubscriber;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;

class GoogleAuthHandlerGuzzle5AuthHandler
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
        $subscriber = new AuthTokenSubscriber($credentials, $this->cacheConfig, $this->cache, $authHttpHandler, $tokenCallback);
        $http->setDefaultOption('auth', 'google_auth');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }

    private function createAuthHttp(ClientInterface $http)
    {
        return new Client(['base_url' => $http->getBaseUrl(), 'defaults' => ['exceptions' => true, 'verify' => $http->getDefaultOption('verify'), 'proxy' => $http->getDefaultOption('proxy'),]]);
    }

    public function attachToken(ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use ($token) {
            return $token['access_token'];
        };
        $subscriber = new ScopedAccessTokenSubscriber($tokenFunc, $scopes, [], $this->cache);
        $http->setDefaultOption('auth', 'scoped');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }

    public function attachKey(ClientInterface $http, $key)
    {
        $subscriber = new SimpleSubscriber(['key' => $key]);
        $http->setDefaultOption('auth', 'simple');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
}
