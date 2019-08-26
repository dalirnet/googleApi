<?php

namespace Monolog\Handler;

use Exception;
use Monolog\Logger;
use RollbarNotifier;

class RollbarHandler extends AbstractProcessingHandler
{
    protected $rollbarNotifier;
    protected $levelMap = array(Logger::DEBUG => 'debug', Logger::INFO => 'info', Logger::NOTICE => 'info', Logger::WARNING => 'warning', Logger::ERROR => 'error', Logger::CRITICAL => 'critical', Logger::ALERT => 'critical', Logger::EMERGENCY => 'critical',);
    protected $initialized = false;
    private $hasRecords = false;

    public function __construct(RollbarNotifier $rollbarNotifier, $level = Logger::ERROR, $bubble = true)
    {
        $this->rollbarNotifier = $rollbarNotifier;
        parent::__construct($level, $bubble);
    }

    public function close()
    {
        $this->flush();
    }

    public function flush()
    {
        if ($this->hasRecords) {
            $this->rollbarNotifier->flush();
            $this->hasRecords = false;
        }
    }

    protected function write(array $record)
    {
        if (!$this->initialized) {

            register_shutdown_function(array($this, 'close'));
            $this->initialized = true;
        }
        $context = $record['context'];
        $payload = array();
        if (isset($context['payload'])) {
            $payload = $context['payload'];
            unset($context['payload']);
        }
        $context = array_merge($context, $record['extra'], array('level' => $this->levelMap[$record['level']], 'monolog_level' => $record['level_name'], 'channel' => $record['channel'], 'datetime' => $record['datetime']->format('U'),));
        if (isset($context['exception']) && $context['exception'] instanceof Exception) {
            $payload['level'] = $context['level'];
            $exception = $context['exception'];
            unset($context['exception']);
            $this->rollbarNotifier->report_exception($exception, $context, $payload);
        } else {
            $this->rollbarNotifier->report_message($record['message'], $context['level'], $context, $payload);
        }
        $this->hasRecords = true;
    }
}
