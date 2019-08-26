<?php

namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\Slack\SlackRecord;
use Monolog\Logger;

class SlackWebhookHandler extends AbstractProcessingHandler
{
    private $webhookUrl;
    private $slackRecord;

    public function __construct($webhookUrl, $channel = null, $username = null, $useAttachment = true, $iconEmoji = null, $useShortAttachment = false, $includeContextAndExtra = false, $level = Logger::CRITICAL, $bubble = true, array $excludeFields = array())
    {
        parent::__construct($level, $bubble);
        $this->webhookUrl = $webhookUrl;
        $this->slackRecord = new SlackRecord($channel, $username, $useAttachment, $iconEmoji, $useShortAttachment, $includeContextAndExtra, $excludeFields, $this->formatter);
    }

    public function getSlackRecord()
    {
        return $this->slackRecord;
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->slackRecord->setFormatter($formatter);
        return $this;
    }

    public function getFormatter()
    {
        $formatter = parent::getFormatter();
        $this->slackRecord->setFormatter($formatter);
        return $formatter;
    }

    protected function write(array $record)
    {
        $postData = $this->slackRecord->getSlackData($record);
        $postString = json_encode($postData);
        $ch = curl_init();
        $options = array(CURLOPT_URL => $this->webhookUrl, CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array('Content-type: application/json'), CURLOPT_POSTFIELDS => $postString);
        if (defined('CURLOPT_SAFE_UPLOAD')) {
            $options[CURLOPT_SAFE_UPLOAD] = true;
        }
        curl_setopt_array($ch, $options);
        Curl\Util::execute($ch);
    }
}
