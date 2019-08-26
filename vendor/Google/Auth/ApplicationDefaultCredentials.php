<?php

namespace Google\Auth;

use Google\Auth\Credentials\AppIdentityCredentials;
use Google\Auth\Credentials\GCECredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use Google\Auth\Subscriber\AuthTokenSubscriber;
use Psr\Cache\CacheItemPoolInterface;

class ApplicationDefaultCredentials
{
    public static function getSubscriber($scope = null, callable $httpHandler = null, array $cacheConfig = null, CacheItemPoolInterface $cache = null)
    {
        $creds = self::getCredentials($scope, $httpHandler);
        return new AuthTokenSubscriber($creds, $cacheConfig, $cache, $httpHandler);
    }

    public static function getCredentials($scope = null, callable $httpHandler = null)
    {
        $creds = CredentialsLoader::fromEnv($scope);
        if (!is_null($creds)) {
            return $creds;
        }
        $creds = CredentialsLoader::fromWellKnownFile($scope);
        if (!is_null($creds)) {
            return $creds;
        }
        if (AppIdentityCredentials::onAppEngine()) {
            return new AppIdentityCredentials($scope);
        }
        if (GCECredentials::onGce($httpHandler)) {
            return new GCECredentials();
        }
        throw new \DomainException(self::notFound());
    }

    private static function notFound()
    {
        $msg = 'Could not load the default credentials. Browse to ';
        $msg .= 'https://developers.google.com';
        $msg .= '/accounts/docs/application-default-credentials';
        $msg .= ' for more information';
        return $msg;
    }

    public static function getMiddleware($scope = null, callable $httpHandler = null, array $cacheConfig = null, CacheItemPoolInterface $cache = null)
    {
        $creds = self::getCredentials($scope, $httpHandler);
        return new AuthTokenMiddleware($creds, $cacheConfig, $cache, $httpHandler);
    }
}
