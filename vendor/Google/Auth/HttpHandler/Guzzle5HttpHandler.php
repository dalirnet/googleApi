<?php

namespace Google\Auth\HttpHandler;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Psr7\Response;
use Psr\HttpMessage\RequestInterface;

class Guzzle5HttpHandler
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function __invoke(RequestInterface $request, array $options = [])
    {
        $request = $this->client->createRequest($request->getMethod(), $request->getUri(), array_merge(['headers' => $request->getHeaders(), 'body' => $request->getBody(),], $options));
        $response = $this->client->send($request);
        return new Response($response->getStatusCode(), $response->getHeaders() ?: [], $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
    }
}
