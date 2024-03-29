<?php

namespace Monolog\Handler;

use Monolog\Handler\SyslogUdp\UdpSocket;
use Monolog\Logger;

class SyslogUdpHandler extends AbstractSyslogHandler
{
    protected $socket;
    protected $ident;

    public function __construct($host, $port = 514, $facility = LOG_USER, $level = Logger::DEBUG, $bubble = true, $ident = 'php')
    {
        parent::__construct($facility, $level, $bubble);
        $this->ident = $ident;
        $this->socket = new UdpSocket($host, $port ?: 514);
    }

    public function close()
    {
        $this->socket->close();
    }

    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    protected function write(array $record)
    {
        $lines = $this->splitMessageIntoLines($record['formatted']);
        $header = $this->makeCommonSyslogHeader($this->logLevels[$record['level']]);
        foreach ($lines as $line) {
            $this->socket->write($line, $header);
        }
    }

    private function splitMessageIntoLines($message)
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        return preg_split('/$\R?^/m', $message, -1, PREG_SPLIT_NO_EMPTY);
    }

    protected function makeCommonSyslogHeader($severity)
    {
        $priority = $severity + $this->facility;
        if (!$pid = getmypid()) {
            $pid = '-';
        }
        if (!$hostname = gethostname()) {
            $hostname = '-';
        }
        return "<$priority>1 " . $this->getDateTime() . " " . $hostname . " " . $this->ident . " " . $pid . " - - ";
    }

    protected function getDateTime()
    {
        return date(\DateTime::RFC3339);
    }
}
