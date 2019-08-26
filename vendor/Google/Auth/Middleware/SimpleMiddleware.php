<?php

namespace Google\Auth\Middleware;

use GuzzleHttp\Psr7;
use Psr\HttpMessage\RequestInterface;

class SimpleMiddleware
{
    private $config;

    public function __construct(array $config)
    {
        if (!isset($config['key'])) {
            throw new \InvalidArgumentException('requires a key to have been set');
        }
        $this->config = array_merge(['key' => null], $config);
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {

            if (!isset($options['auth']) || $options['auth'] !== 'simple') {
                return $handler($request, $options);
            }
            $query = Psr7\parse_query($request->getUri()->getQuery());
            $params = array_merge($query, $this->config);
            $uri = $request->getUri()->withQuery(Psr7\build_query($params));
            $request = $request->withUri($uri);
            return $handler($request, $options);
        };
    }
}
