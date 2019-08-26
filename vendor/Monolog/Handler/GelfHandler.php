<?php

namespace Monolog\Handler;

use Gelf\IMessagePublisher;
use Gelf\Publisher;
use Gelf\PublisherInterface;
use InvalidArgumentException;
use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Logger;

class GelfHandler extends AbstractProcessingHandler
{
    protected $publisher;

    public function __construct($publisher, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        if (!$publisher instanceof Publisher && !$publisher instanceof IMessagePublisher && !$publisher instanceof PublisherInterface) {
            throw new InvalidArgumentException('Invalid publisher, expected a Gelf\Publisher, Gelf\IMessagePublisher or Gelf\PublisherInterface instance');
        }
        $this->publisher = $publisher;
    }

    public function close()
    {
        $this->publisher = null;
    }

    protected function write(array $record)
    {
        $this->publisher->publish($record['formatted']);
    }

    protected function getDefaultFormatter()
    {
        return new GelfMessageFormatter();
    }
}
