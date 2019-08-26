<?php

namespace Google\Auth\Subscriber;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;

class SimpleSubscriber implements SubscriberInterface
{
    private $config;

    public function __construct(array $config)
    {
        if (!isset($config['key'])) {
            throw new \InvalidArgumentException('requires a key to have been set');
        }
        $this->config = array_merge([], $config);
    }

    public function getEvents()
    {
        return ['before' => ['onBefore', RequestEvents::SIGN_REQUEST]];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        if ($request->getConfig()['auth'] != 'simple') {
            return;
        }
        $request->getQuery()->overwriteWith($this->config);
    }
}
