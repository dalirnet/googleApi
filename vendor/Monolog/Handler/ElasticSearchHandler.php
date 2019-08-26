<?php

namespace Monolog\Handler;

use Elastica\Client;
use Elastica\Exception\ExceptionInterface;
use Monolog\Formatter\ElasticaFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

class ElasticSearchHandler extends AbstractProcessingHandler
{
    protected $client;
    protected $options = array();

    public function __construct(Client $client, array $options = array(), $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
        $this->options = array_merge(array('index' => 'monolog', 'type' => 'record', 'ignore_error' => false,), $options);
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        if ($formatter instanceof ElasticaFormatter) {
            return parent::setFormatter($formatter);
        }
        throw new \InvalidArgumentException('ElasticSearchHandler is only compatible with ElasticaFormatter');
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function handleBatch(array $records)
    {
        $documents = $this->getFormatter()->formatBatch($records);
        $this->bulkSend($documents);
    }

    protected function bulkSend(array $documents)
    {
        try {
            $this->client->addDocuments($documents);
        } catch (ExceptionInterface $e) {
            if (!$this->options['ignore_error']) {
                throw new \RuntimeException("Error sending messages to Elasticsearch", 0, $e);
            }
        }
    }

    protected function write(array $record)
    {
        $this->bulkSend(array($record['formatted']));
    }

    protected function getDefaultFormatter()
    {
        return new ElasticaFormatter($this->options['index'], $this->options['type']);
    }
}
