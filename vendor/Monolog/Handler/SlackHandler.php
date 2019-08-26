<?php

namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\Slack\SlackRecord;
use Monolog\Logger;

class SlackHandler extends SocketHandler
{
    private $token;
    private $slackRecord;

    public function __construct($token, $channel, $username = null, $useAttachment = true, $iconEmoji = null, $level = Logger::CRITICAL, $bubble = true, $useShortAttachment = false, $includeContextAndExtra = false, array $excludeFields = array())
    {
        if (!extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use the SlackHandler');
        }
        parent::__construct('ssl://slack.com:443', $level, $bubble);
        $this->slackRecord = new SlackRecord($channel, $username, $useAttachment, $iconEmoji, $useShortAttachment, $includeContextAndExtra, $excludeFields, $this->formatter);
        $this->token = $token;
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

    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);
        return $this->buildHeader($content) . $content;
    }

    private function buildContent($record)
    {
        $dataArray = $this->prepareContentData($record);
        return http_build_query($dataArray);
    }

    protected function prepareContentData($record)
    {
        $dataArray = $this->slackRecord->getSlackData($record);
        $dataArray['token'] = $this->token;
        if (!empty($dataArray['attachments'])) {
            $dataArray['attachments'] = json_encode($dataArray['attachments']);
        }
        return $dataArray;
    }

    private function buildHeader($content)
    {
        $header = "POST /api/chat.postMessage HTTP/1.1\r\n";
        $header .= "Host: slack.com\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "\r\n";
        return $header;
    }

    protected function write(array $record)
    {
        parent::write($record);
        $this->finalizeWrite();
    }

    protected function finalizeWrite()
    {
        $res = $this->getResource();
        if (is_resource($res)) {
            @fread($res, 2048);
        }
        $this->closeSocket();
    }

    protected function getAttachmentColor($level)
    {
        trigger_error('SlackHandler::getAttachmentColor() is deprecated. Use underlying SlackRecord instead.', E_USER_DEPRECATED);
        return $this->slackRecord->getAttachmentColor($level);
    }

    protected function stringify($fields)
    {
        trigger_error('SlackHandler::stringify() is deprecated. Use underlying SlackRecord instead.', E_USER_DEPRECATED);
        return $this->slackRecord->stringify($fields);
    }
}
