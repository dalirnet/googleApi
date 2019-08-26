<?php

namespace Monolog\Formatter;

use Exception;
use Throwable;

class JsonFormatter extends NormalizerFormatter
{
    const BATCH_MODE_JSON = 1;
    const BATCH_MODE_NEWLINES = 2;
    protected $batchMode;
    protected $appendNewline;
    protected $includeStacktraces = false;

    public function __construct($batchMode = self::BATCH_MODE_JSON, $appendNewline = true)
    {
        $this->batchMode = $batchMode;
        $this->appendNewline = $appendNewline;
    }

    public function getBatchMode()
    {
        return $this->batchMode;
    }

    public function isAppendingNewlines()
    {
        return $this->appendNewline;
    }

    public function formatBatch(array $records)
    {
        switch ($this->batchMode) {
            case static::BATCH_MODE_NEWLINES:
                return $this->formatBatchNewlines($records);
            case static::BATCH_MODE_JSON:
            default:
                return $this->formatBatchJson($records);
        }
    }

    protected function formatBatchNewlines(array $records)
    {
        $instance = $this;
        $oldNewline = $this->appendNewline;
        $this->appendNewline = false;
        array_walk($records, function (&$value, $key) use ($instance) {
            $value = $instance->format($value);
        });
        $this->appendNewline = $oldNewline;
        return implode("\n", $records);
    }

    public function format(array $record)
    {
        return $this->toJson($this->normalize($record), true) . ($this->appendNewline ? "\n" : '');
    }

    protected function normalize($data)
    {
        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = array();
            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ >= 1000) {
                    $normalized['...'] = 'Over 1000 items, aborting normalization';
                    break;
                }
                $normalized[$key] = $this->normalize($value);
            }
            return $normalized;
        }
        if ($data instanceof Exception || $data instanceof Throwable) {
            return $this->normalizeException($data);
        }
        return $data;
    }

    protected function normalizeException($e)
    {

        if (!$e instanceof Exception && !$e instanceof Throwable) {
            throw new \InvalidArgumentException('Exception/Throwable expected, got ' . gettype($e) . ' / ' . get_class($e));
        }
        $data = array('class' => get_class($e), 'message' => $e->getMessage(), 'code' => $e->getCode(), 'file' => $e->getFile() . ':' . $e->getLine(),);
        if ($this->includeStacktraces) {
            $trace = $e->getTrace();
            foreach ($trace as $frame) {
                if (isset($frame['file'])) {
                    $data['trace'][] = $frame['file'] . ':' . $frame['line'];
                } elseif (isset($frame['function']) && $frame['function'] === '{closure}') {

                    $data['trace'][] = $frame['function'];
                } else {

                    $data['trace'][] = $this->normalize($frame);
                }
            }
        }
        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous);
        }
        return $data;
    }

    protected function formatBatchJson(array $records)
    {
        return $this->toJson($this->normalize($records), true);
    }

    public function includeStacktraces($include = true)
    {
        $this->includeStacktraces = $include;
    }
}
