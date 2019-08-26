<?php

namespace Guzzle\Http\Exception;

use Psr\HttpMessage\RequestInterface;

class ConnectException extends RequestException
{
    public function __construct($message, RequestInterface $request, \Exception $previous = null, array $handlerContext = [])
    {
        parent::__construct($message, $request, null, $previous, $handlerContext);
    }

    public function getResponse()
    {
        return null;
    }

    public function hasResponse()
    {
        return false;
    }
}
