<?php

namespace Google\Auth\HttpHandler;

use Guzzle\Http\ClientInterface;
use Psr\HttpMessage\RequestInterface;

class Guzzle6HttpHandler
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function __invoke(RequestInterface $request, array $options = [])
    {
        return $this->client->send($request, $options);
    }
}
