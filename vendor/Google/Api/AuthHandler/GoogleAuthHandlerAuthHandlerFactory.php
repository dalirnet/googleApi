<?php

namespace Google\Api\AuthHandler;

use Guzzle\Http\ClientInterface;

class GoogleAuthHandlerAuthHandlerFactory
{
    public static function build($cache = null, array $cacheConfig = [])
    {
        $version = ClientInterface::VERSION;
        switch ($version[0]) {
            case '5':
                return new GoogleAuthHandlerGuzzle5AuthHandler($cache, $cacheConfig);
            case '6':
                return new GoogleAuthHandlerGuzzle6AuthHandler($cache, $cacheConfig);
            default:
                throw new Exception('Version not supported');
        }
    }
}
