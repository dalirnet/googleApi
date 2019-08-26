<?php

namespace Psr\HttpMessage;

interface ResponseInterface extends MessageInterface
{
    public function getStatusCode();

    public function withStatus($code, $reasonPhrase = '');

    public function getReasonPhrase();
}
