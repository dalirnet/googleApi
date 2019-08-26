<?php

namespace Guzzle\Http\Handler;

use Psr\HttpMessage\RequestInterface;

interface CurlFactoryInterface
{
    public function create(RequestInterface $request, array $options);

    public function release(EasyHandle $easy);
}
