<?php

namespace Google\Auth\Subscriber;

use Google\Auth\CacheTrait;
use Google\Auth\FetchAuthTokenInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use Psr\Cache\CacheItemPoolInterface;

class AuthTokenSubscriber implements SubscriberInterface
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

    public function getEvents()
    {
        return ['before' => ['onBefore', RequestEvents::SIGN_REQUEST]];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getConfig()['auth'] != 'google_auth') {
            return;
        }
        $cached = $this->getCachedValue();
        if (!empty($cached)) {
            $request->setHeader('Authorization', 'Bearer ' . $cached);
            return;
        }
        $auth_tokens = $this->fetcher->fetchAuthToken($this->httpHandler);
        if (array_key_exists('access_token', $auth_tokens)) {
            $request->setHeader('Authorization', 'Bearer ' . $auth_tokens['access_token']);
            $this->setCachedValue($auth_tokens['access_token']);
            if ($this->tokenCallback) {
                call_user_func($this->tokenCallback, $this->getFullCacheKey(), $auth_tokens['access_token']);
            }
        }
    }
}
