<?php

namespace Guzzle\Http;

use Guzzle\Http\Promise\PromiseInterface;
use Guzzle\Http\Promise\RejectedPromise;
use Guzzle\Http\Psr7;
use Psr\HttpMessage\RequestInterface;

class RetryMiddleware
{
    private $nextHandler;
    private $decider;

    public function __construct(callable $decider, callable $nextHandler, callable $delay = null)
    {
        $this->decider = $decider;
        $this->nextHandler = $nextHandler;
        $this->delay = $delay ?: __CLASS__ . '::exponentialDelay';
    }

    public static function exponentialDelay($retries)
    {
        return (int)pow(2, $retries - 1);
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        if (!isset($options['retries'])) {
            $options['retries'] = 0;
        }
        $fn = $this->nextHandler;
        return $fn($request, $options)->then($this->onFulfilled($request, $options), $this->onRejected($request, $options));
    }

    private function onFulfilled(RequestInterface $req, array $options)
    {
        return function ($value) use ($req, $options) {
            if (!call_user_func($this->decider, $options['retries'], $req, $value, null)) {
                return $value;
            }
            return $this->doRetry($req, $options);
        };
    }

    private function doRetry(RequestInterface $request, array $options)
    {
        $options['delay'] = call_user_func($this->delay, ++$options['retries']);
        return $this($request, $options);
    }

    private function onRejected(RequestInterface $req, array $options)
    {
        return function ($reason) use ($req, $options) {
            if (!call_user_func($this->decider, $options['retries'], $req, null, $reason)) {
                return new RejectedPromise($reason);
            }
            return $this->doRetry($req, $options);
        };
    }
}
