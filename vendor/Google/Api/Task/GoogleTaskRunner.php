<?php

namespace Google\Api\Task;

class GoogleTaskRunner
{
    const TASK_RETRY_NEVER = 0;
    const TASK_RETRY_ONCE = 1;
    const TASK_RETRY_ALWAYS = -1;
    protected $retryMap = ['500' => self::TASK_RETRY_ALWAYS, '503' => self::TASK_RETRY_ALWAYS, 'rateLimitExceeded' => self::TASK_RETRY_ALWAYS, 'userRateLimitExceeded' => self::TASK_RETRY_ALWAYS, 6 => self::TASK_RETRY_ALWAYS, 7 => self::TASK_RETRY_ALWAYS, 28 => self::TASK_RETRY_ALWAYS, 35 => self::TASK_RETRY_ALWAYS, 52 => self::TASK_RETRY_ALWAYS];
    private $maxDelay = 60;
    private $delay = 1;
    private $factor = 2;
    private $jitter = 0.5;
    private $attempts = 0;
    private $maxAttempts = 1;
    private $name;
    private $action;
    private $arguments;

    public function __construct($config, $name, $action, array $arguments = array())
    {
        if (isset($config['initial_delay'])) {
            if ($config['initial_delay'] < 0) {
                throw new GoogleTaskException('Task configuration `initial_delay` must not be negative.');
            }
            $this->delay = $config['initial_delay'];
        }
        if (isset($config['max_delay'])) {
            if ($config['max_delay'] <= 0) {
                throw new GoogleTaskException('Task configuration `max_delay` must be greater than 0.');
            }
            $this->maxDelay = $config['max_delay'];
        }
        if (isset($config['factor'])) {
            if ($config['factor'] <= 0) {
                throw new GoogleTaskException('Task configuration `factor` must be greater than 0.');
            }
            $this->factor = $config['factor'];
        }
        if (isset($config['jitter'])) {
            if ($config['jitter'] <= 0) {
                throw new GoogleTaskException('Task configuration `jitter` must be greater than 0.');
            }
            $this->jitter = $config['jitter'];
        }
        if (isset($config['retries'])) {
            if ($config['retries'] < 0) {
                throw new GoogleTaskException('Task configuration `retries` must not be negative.');
            }
            $this->maxAttempts += $config['retries'];
        }
        if (!is_callable($action)) {
            throw new GoogleTaskException('Task argument `$action` must be a valid callable.');
        }
        $this->name = $name;
        $this->action = $action;
        $this->arguments = $arguments;
    }

    public function run()
    {
        while ($this->attempt()) {
            try {
                return call_user_func_array($this->action, $this->arguments);
            } catch (Google_Service_Exception $exception) {
                $allowedRetries = $this->allowedRetries($exception->getCode(), $exception->getErrors());
                if (!$this->canAttempt() || !$allowedRetries) {
                    throw $exception;
                }
                if ($allowedRetries > 0) {
                    $this->maxAttempts = min($this->maxAttempts, $this->attempts + $allowedRetries);
                }
            }
        }
    }

    public function attempt()
    {
        if (!$this->canAttempt()) {
            return false;
        }
        if ($this->attempts > 0) {
            $this->backOff();
        }
        $this->attempts++;
        return true;
    }

    public function canAttempt()
    {
        return $this->attempts < $this->maxAttempts;
    }

    private function backOff()
    {
        $delay = $this->getDelay();
        usleep($delay * 1000000);
    }

    private function getDelay()
    {
        $jitter = $this->getJitter();
        $factor = $this->attempts > 1 ? $this->factor + $jitter : 1 + abs($jitter);
        return $this->delay = min($this->maxDelay, $this->delay * $factor);
    }

    private function getJitter()
    {
        return $this->jitter * 2 * mt_rand() / mt_getrandmax() - $this->jitter;
    }

    public function allowedRetries($code, $errors = array())
    {
        if (isset($this->retryMap[$code])) {
            return $this->retryMap[$code];
        }
        if (!empty($errors) && isset($errors[0]['reason']) && isset($this->retryMap[$errors[0]['reason']])) {
            return $this->retryMap[$errors[0]['reason']];
        }
        return 0;
    }

    public function setRetryMap($retryMap)
    {
        $this->retryMap = $retryMap;
    }
}
