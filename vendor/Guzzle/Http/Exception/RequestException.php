<?php

namespace Guzzle\Http\Exception;

use Guzzle\Http\Promise\PromiseInterface;
use Psr\HttpMessage\RequestInterface;
use Psr\HttpMessage\ResponseInterface;

class RequestException extends TransferException
{
    private $request;
    private $response;
    private $handlerContext;

    public function __construct($message, RequestInterface $request, ResponseInterface $response = null, \Exception $previous = null, array $handlerContext = [])
    {

        $code = $response && !($response instanceof PromiseInterface) ? $response->getStatusCode() : 0;
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }

    public static function wrapException(RequestInterface $request, \Exception $e)
    {
        return $e instanceof RequestException ? $e : new RequestException($e->getMessage(), $request, null, $e);
    }

    public static function create(RequestInterface $request, ResponseInterface $response = null, \Exception $previous = null, array $ctx = [])
    {
        if (!$response) {
            return new self('Error completing request', $request, null, $previous, $ctx);
        }
        $level = floor($response->getStatusCode() / 100);
        if ($level == '4') {
            $label = 'Client error';
            $className = __NAMESPACE__ . '\\ClientException';
        } elseif ($level == '5') {
            $label = 'Server error';
            $className = __NAMESPACE__ . '\\ServerException';
        } else {
            $label = 'Unsuccessful request';
            $className = __CLASS__;
        }
        $message = sprintf('%s: `%s` resulted in a `%s` response', $label, $request->getMethod() . ' ' . $request->getUri(), $response->getStatusCode() . ' ' . $response->getReasonPhrase());
        $summary = static::getResponseBodySummary($response);
        if ($summary !== null) {
            $message .= ":\n{$summary}\n";
        }
        return new $className($message, $request, $response, $previous, $ctx);
    }

    public static function getResponseBodySummary(ResponseInterface $response)
    {
        $body = $response->getBody();
        if (!$body->isSeekable()) {
            return null;
        }
        $size = $body->getSize();
        $summary = $body->read(120);
        $body->rewind();
        if ($size > 120) {
            $summary .= ' (truncated...)';
        }
        if (preg_match('/[^\pL\pM\pN\pP\pS\pZ\n\r\t]/', $summary)) {
            return null;
        }
        return $summary;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function hasResponse()
    {
        return $this->response !== null;
    }

    public function getHandlerContext()
    {
        return $this->handlerContext;
    }
}
