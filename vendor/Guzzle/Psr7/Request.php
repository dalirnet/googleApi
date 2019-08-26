<?php

namespace Guzzle\Psr7;

use InvalidArgumentException;
use Psr\HttpMessage\RequestInterface;
use Psr\HttpMessage\UriInterface;

include dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . "promise" . DIRECTORY_SEPARATOR . "functions_include.php";
include __DIR__ . DIRECTORY_SEPARATOR . "functions_include.php";

class Request implements RequestInterface
{
    use MessageTrait {
        withHeader as protected withParentHeader;
    }
    private $method;
    private $requestTarget;
    private $uri;

    public function __construct($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        } elseif (!($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException('URI must be a string or Psr\Http\Message\UriInterface');
        }
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $protocolVersion;
        $host = $uri->getHost();
        if ($host && !$this->hasHeader('Host')) {
            $this->updateHostFromUri($host);
        }
        if ($body) {
            $this->stream = stream_for($body);
        }
    }

    private function updateHostFromUri($host)
    {

        if ($port = $this->uri->getPort()) {
            $host .= ':' . $port;
        }
        $this->headerLines = ['Host' => [$host]] + $this->headerLines;
        $this->headers = ['host' => [$host]] + $this->headers;
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        $target = $this->uri->getPath();
        if ($target == null) {
            $target = '/';
        }
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }
        return $target;
    }

    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $new = clone $this;
        $new->uri = $uri;
        if (!$preserveHost) {
            if ($host = $uri->getHost()) {
                $new->updateHostFromUri($host);
            }
        }
        return $new;
    }

    public function withHeader($header, $value)
    {

        $newInstance = $this->withParentHeader($header, $value);
        return $newInstance;
    }
}
