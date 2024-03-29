<?php

namespace Psr\HttpMessage;

interface RequestInterface extends MessageInterface
{
    public function getRequestTarget();

    public function withRequestTarget($requestTarget);

    public function getMethod();

    public function withMethod($method);

    public function getUri();

    public function withUri(UriInterface $uri, $preserveHost = false);
}
