<?php

namespace Google\Api;

class GoogleService
{
    public $batchPath;
    public $rootUrl;
    public $version;
    public $servicePath;
    public $availableScopes;
    public $resource;
    private $client;

    public function __construct(GoogleClient $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function createBatch()
    {
        return new GoogleHttpBatch($this->client, false, $this->rootUrl, $this->batchPath);
    }
}
