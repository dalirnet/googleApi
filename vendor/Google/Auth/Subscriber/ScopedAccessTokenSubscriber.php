<?php

namespace Google\Auth\Subscriber;

use Google\Auth\CacheTrait;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use Psr\Cache\CacheItemPoolInterface;

class ScopedAccessTokenSubscriber implements SubscriberInterface
{
    use CacheTrait;
    const DEFAULT_CACHE_LIFETIME = 1500;
    private $cache;
    private $tokenFunc;
    private $scopes;
    private $cacheConfig;

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

    public function getEvents()
    {
        return ['before' => ['onBefore', RequestEvents::SIGN_REQUEST]];
    }

    public function onBefore(BeforeEvent $event)
    {

        $request = $event->getRequest();
        if ($request->getConfig()['auth'] != 'scoped') {
            return;
        }
        $auth_header = 'Bearer ' . $this->fetchToken();
        $request->setHeader('Authorization', $auth_header);
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
