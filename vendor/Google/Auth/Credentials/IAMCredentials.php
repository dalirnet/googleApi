<?php

namespace Google\Auth\Credentials;

class IAMCredentials
{
    const SELECTOR_KEY = 'x-goog-iam-authority-selector';
    const TOKEN_KEY = 'x-goog-iam-authorization-token';
    private $selector;
    private $token;

    public function __construct($selector, $token)
    {
        if (!is_string($selector)) {
            throw new \InvalidArgumentException('selector must be a string');
        }
        if (!is_string($token)) {
            throw new \InvalidArgumentException('token must be a string');
        }
        $this->selector = $selector;
        $this->token = $token;
    }

    public function getUpdateMetadataFunc()
    {
        return array($this, 'updateMetadata');
    }

    public function updateMetadata($metadata, $unusedAuthUri = null, callable $httpHandler = null)
    {
        $metadata_copy = $metadata;
        $metadata_copy[self::SELECTOR_KEY] = $this->selector;
        $metadata_copy[self::TOKEN_KEY] = $this->token;
        return $metadata_copy;
    }
}
