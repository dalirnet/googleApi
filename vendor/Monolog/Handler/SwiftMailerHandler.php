<?php

namespace Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Swift;

class SwiftMailerHandler extends MailHandler
{
    protected $mailer;
    private $messageTemplate;

    public function __construct(\Swift_Mailer $mailer, $message, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->mailer = $mailer;
        $this->messageTemplate = $message;
    }

    public function __get($name)
    {
        if ($name === 'message') {
            trigger_error('SwiftMailerHandler->message is deprecated, use ->buildMessage() instead to retrieve the message', E_USER_DEPRECATED);
            return $this->buildMessage(null, array());
        }
        throw new \InvalidArgumentException('Invalid property ' . $name);
    }

    protected function buildMessage($content, array $records)
    {
        $message = null;
        if ($this->messageTemplate instanceof \Swift_Message) {
            $message = clone $this->messageTemplate;
            $message->generateId();
        } elseif (is_callable($this->messageTemplate)) {
            $message = call_user_func($this->messageTemplate, $content, $records);
        }
        if (!$message instanceof \Swift_Message) {
            throw new \InvalidArgumentException('Could not resolve message as instance of Swift_Message or a callable returning it');
        }
        if ($records) {
            $subjectFormatter = new LineFormatter($message->getSubject());
            $message->setSubject($subjectFormatter->format($this->getHighestRecord($records)));
        }
        $message->setBody($content);
        if (version_compare(Swift::VERSION, '6.0.0', '>=')) {
            $message->setDate(new \DateTimeImmutable());
        } else {
            $message->setDate(time());
        }
        return $message;
    }

    protected function send($content, array $records)
    {
        $this->mailer->send($this->buildMessage($content, $records));
    }
}
