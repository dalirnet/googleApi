<?php

namespace Monolog\Handler;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Sdk;
use Monolog\Formatter\ScalarFormatter;
use Monolog\Logger;

class DynamoDbHandler extends AbstractProcessingHandler
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s.uO';
    protected $client;
    protected $table;
    protected $version;
    protected $marshaler;

    public function __construct(DynamoDbClient $client, $table, $level = Logger::DEBUG, $bubble = true)
    {
        if (defined('Aws\Sdk::VERSION') && version_compare(Sdk::VERSION, '3.0', '>=')) {
            $this->version = 3;
            $this->marshaler = new Marshaler;
        } else {
            $this->version = 2;
        }
        $this->client = $client;
        $this->table = $table;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record)
    {
        $filtered = $this->filterEmptyFields($record['formatted']);
        if ($this->version === 3) {
            $formatted = $this->marshaler->marshalItem($filtered);
        } else {
            $formatted = $this->client->formatAttributes($filtered);
        }
        $this->client->putItem(array('TableName' => $this->table, 'Item' => $formatted,));
    }

    protected function filterEmptyFields(array $record)
    {
        return array_filter($record, function ($value) {
            return !empty($value) || false === $value || 0 === $value;
        });
    }

    protected function getDefaultFormatter()
    {
        return new ScalarFormatter(self::DATE_FORMAT);
    }
}
