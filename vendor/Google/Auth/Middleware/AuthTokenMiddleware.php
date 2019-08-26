<?php

namespace Google\Auth\Middleware;

use Google\Auth\CacheTrait;
use Google\Auth\FetchAuthTokenInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\HttpMessage\RequestInterface;

class AuthTokenMiddleware
{
    use CacheTrait;
    const DEFAULT_CACHE_LIFETIME = 1500;
    private $cache;
    private $httpHandler;
    private $fetcher;
    private $cacheConfig;
    private $tokenCallback;

    public function __construct(FetchAuthTokenInterface $fetcher, array $cacheConfig = null, CacheItemPoolInterface $cache = null, callable $httpHandler = null, callable $tokenCallback = null)
    {
        $this->fetcher = $fetcher;
        $this->httpHandler = $httpHandler;
        $this->tokenCallback = $tokenCallback;
        if (!is_null($cache)) {
            $this->cache = $cache;
            $this->cacheConfig = array_merge(['lifetime' => self::DEFAULT_CACHE_LIFETIME, 'prefix' => '',], $cacheConfig);
        }
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {

            if (!isset($options['auth']) || $options['auth'] !== 'google_auth') {
                return $handler($request, $options);
            }
            $request = $request->withHeader('Authorization', 'Bearer ' . $this->fetchToken());
            return $handler($request, $options);
        };
    }

    private function fetchToken()
    {
        $cached = $this->getCachedValue();
        if (!empty($cached)) {
            return $cached;
        }
        $auth_tokens = $this->fetcher->fetchAuthToken($this->httpHandler);
        if (array_key_exists('access_token', $auth_tokens)) {
            $this->setCachedValue($auth_tokens['access_token']);
            if ($this->tokenCallback) {
                call_user_func($this->tokenCallback, $this->getFullCacheKey(), $auth_tokens['access_token']);
            }
            return $auth_tokens['access_token'];
        }
    }
}
