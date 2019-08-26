<?php

namespace Google\Auth\Middleware;

use Google\Auth\CacheTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\HttpMessage\RequestInterface;

class ScopedAccessTokenMiddleware
{
    use CacheTrait;
    const DEFAULT_CACHE_LIFETIME = 1500;
    private $cache;
    private $httpHandler;
    private $fetcher;
    private $cacheConfig;
    private $tokenFunc;
    private $scopes;

    public function __construct(callable $tokenFunc, $scopes, array $cacheConfig = null, CacheItemPoolInterface $cache = null)
    {
        $this->tokenFunc = $tokenFunc;
        if (!(is_string($scopes) || is_array($scopes))) {
            throw new \InvalidArgumentException('wants scope should be string or array');
        }
        $this->scopes = $scopes;
        if (!is_null($cache)) {
            $this->cache = $cache;
            $this->cacheConfig = array_merge(['lifetime' => self::DEFAULT_CACHE_LIFETIME, 'prefix' => '',], $cacheConfig);
        }
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {

            if (!isset($options['auth']) || $options['auth'] !== 'scoped') {
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
        $token = call_user_func($this->tokenFunc, $this->scopes);
        $this->setCachedValue($token);
        return $token;
    }

    private function getCacheKey()
    {
        $key = null;
        if (is_string($this->scopes)) {
            $key .= $this->scopes;
        } elseif (is_array($this->scopes)) {
            $key .= implode(':', $this->scopes);
        }
        return $key;
    }
}
