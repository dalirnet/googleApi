<?php

namespace Guzzle\Http\Cookie;

use Psr\HttpMessage\RequestInterface;
use Psr\HttpMessage\ResponseInterface;

interface CookieJarInterface extends \Countable, \IteratorAggregate
{
    public function withCookieHeader(RequestInterface $request);

    public function extractCookies(RequestInterface $request, ResponseInterface $response);

    public function setCookie(SetCookie $cookie);

    public function clear($domain = null, $path = null, $name = null);

    public function clearSessionCookies();

    public function toArray();
}
