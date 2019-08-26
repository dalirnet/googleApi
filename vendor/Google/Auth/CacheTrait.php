<?php

namespace Google\Auth;

trait CacheTrait
{
    private function getCachedValue()
    {
        if (is_null($this->cache)) {
            return;
        }
        $key = $this->getFullCacheKey();
        if (is_null($key)) {
            return;
        }
        $cacheItem = $this->cache->getItem($key);
        return $cacheItem->get();
    }

    private function getFullCacheKey()
    {
        if (isset($this->fetcher)) {
            $fetcherKey = $this->fetcher->getCacheKey();
        } else {
            $fetcherKey = $this->getCacheKey();
        }
        if (is_null($fetcherKey)) {
            return;
        }
        $key = $this->cacheConfig['prefix'] . $fetcherKey;
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '-', $key);
    }

    private function setCachedValue($v)
    {
        if (is_null($this->cache)) {
            return;
        }
        $key = $this->getFullCacheKey();
        if (is_null($key)) {
            return;
        }
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set($v);
        $cacheItem->expiresAfter($this->cacheConfig['lifetime']);
        return $this->cache->save($cacheItem);
    }
}
