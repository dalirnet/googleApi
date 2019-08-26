<?php

namespace Guzzle\Http;

use Psr\HttpMessage\RequestInterface;

interface ClientInterface
{
    const VERSION = '6.2.0';

    public function send(RequestInterface $request, array $options = []);

    public function sendAsync(RequestInterface $request, array $options = []);

    public function request($method, $uri = null, array $options = []);

    public function requestAsync($method, $uri, array $options = []);

    public function getConfig($option = null);
}
