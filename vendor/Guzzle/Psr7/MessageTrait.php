<?php

namespace Guzzle\Psr7;

use Psr\HttpMessage\StreamInterface;

trait MessageTrait
{
    private $headers = [];
    private $headerLines = [];
    private $protocol = '1.1';
    private $stream;

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
        if ($this->protocol === $version) {
            return $this;
        }
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headerLines;
    }

    public function getHeaderLine($header)
    {
        return implode(', ', $this->getHeader($header));
    }

    public function getHeader($header)
    {
        $name = strtolower($header);
        return isset($this->headers[$name]) ? $this->headers[$name] : [];
    }

    public function withAddedHeader($header, $value)
    {
        if (!$this->hasHeader($header)) {
            return $this->withHeader($header, $value);
        }
        $new = clone $this;
        $new->headers[strtolower($header)][] = $value;
        $new->headerLines[$header][] = $value;
        return $new;
    }

    public function hasHeader($header)
    {
        return isset($this->headers[strtolower($header)]);
    }

    public function withHeader($header, $value)
    {
        $new = clone $this;
        $header = trim($header);
        $name = strtolower($header);
        if (!is_array($value)) {
            $new->headers[$name] = [trim($value)];
        } else {
            $new->headers[$name] = $value;
            foreach ($new->headers[$name] as &$v) {
                $v = trim($v);
            }
        }
        foreach (array_keys($new->headerLines) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headerLines[$key]);
            }
        }
        $new->headerLines[$header] = $new->headers[$name];
        return $new;
    }

    public function withoutHeader($header)
    {
        if (!$this->hasHeader($header)) {
            return $this;
        }
        $new = clone $this;
        $name = strtolower($header);
        unset($new->headers[$name]);
        foreach (array_keys($new->headerLines) as $key) {
            if (strtolower($key) === $name) {
                unset($new->headerLines[$key]);
            }
        }
        return $new;
    }

    public function getBody()
    {
        if (!$this->stream) {
            $this->stream = stream_for('');
        }
        return $this->stream;
    }

    public function withBody(StreamInterface $body)
    {
        if ($body === $this->stream) {
            return $this;
        }
        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    private function setHeaders(array $headers)
    {
        $this->headerLines = $this->headers = [];
        foreach ($headers as $header => $value) {
            $header = trim($header);
            $name = strtolower($header);
            if (!is_array($value)) {
                $value = trim($value);
                $this->headers[$name][] = $value;
                $this->headerLines[$header][] = $value;
            } else {
                foreach ($value as $v) {
                    $v = trim($v);
                    $this->headers[$name][] = $v;
                    $this->headerLines[$header][] = $v;
                }
            }
        }
    }
}
