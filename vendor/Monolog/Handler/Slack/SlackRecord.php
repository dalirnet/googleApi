<?php

namespace Monolog\Handler\Slack;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

class SlackRecord
{
    const COLOR_DANGER = 'danger';
    const COLOR_WARNING = 'warning';
    const COLOR_GOOD = 'good';
    const COLOR_DEFAULT = '#e3e4e6';
    private $channel;
    private $username;
    private $userIcon;
    private $useAttachment;
    private $useShortAttachment;
    private $includeContextAndExtra;
    private $excludeFields;
    private $formatter;
    private $normalizerFormatter;

    public function __construct($channel = null, $username = null, $useAttachment = true, $userIcon = null, $useShortAttachment = false, $includeContextAndExtra = false, array $excludeFields = array(), FormatterInterface $formatter = null)
    {
        $this->channel = $channel;
        $this->username = $username;
        $this->userIcon = trim($userIcon, ':');
        $this->useAttachment = $useAttachment;
        $this->useShortAttachment = $useShortAttachment;
        $this->includeContextAndExtra = $includeContextAndExtra;
        $this->excludeFields = $excludeFields;
        $this->formatter = $formatter;
        if ($this->includeContextAndExtra) {
            $this->normalizerFormatter = new NormalizerFormatter();
        }
    }

    public function getSlackData(array $record)
    {
        $dataArray = array();
        $record = $this->excludeFields($record);
        if ($this->username) {
            $dataArray['username'] = $this->username;
        }
        if ($this->channel) {
            $dataArray['channel'] = $this->channel;
        }
        if ($this->formatter && !$this->useAttachment) {
            $message = $this->formatter->format($record);
        } else {
            $message = $record['message'];
        }
        if ($this->useAttachment) {
            $attachment = array('fallback' => $message, 'text' => $message, 'color' => $this->getAttachmentColor($record['level']), 'fields' => array(), 'mrkdwn_in' => array('fields'), 'ts' => $record['datetime']->getTimestamp());
            if ($this->useShortAttachment) {
                $attachment['title'] = $record['level_name'];
            } else {
                $attachment['title'] = 'Message';
                $attachment['fields'][] = $this->generateAttachmentField('Level', $record['level_name']);
            }
            if ($this->includeContextAndExtra) {
                foreach (array('extra', 'context') as $key) {
                    if (empty($record[$key])) {
                        continue;
                    }
                    if ($this->useShortAttachment) {
                        $attachment['fields'][] = $this->generateAttachmentField(ucfirst($key), $record[$key]);
                    } else {

                        $attachment['fields'] = array_merge($attachment['fields'], $this->generateAttachmentFields($record[$key]));
                    }
                }
            }
            $dataArray['attachments'] = array($attachment);
        } else {
            $dataArray['text'] = $message;
        }
        if ($this->userIcon) {
            if (filter_var($this->userIcon, FILTER_VALIDATE_URL)) {
                $dataArray['icon_url'] = $this->userIcon;
            } else {
                $dataArray['icon_emoji'] = ":{$this->userIcon}:";
            }
        }
        return $dataArray;
    }

    private function excludeFields(array $record)
    {
        foreach ($this->excludeFields as $field) {
            $keys = explode('.', $field);
            $node = &$record;
            $lastKey = end($keys);
            foreach ($keys as $key) {
                if (!isset($node[$key])) {
                    break;
                }
                if ($lastKey === $key) {
                    unset($node[$key]);
                    break;
                }
                $node = &$node[$key];
            }
        }
        return $record;
    }

    public function getAttachmentColor($level)
    {
        switch (true) {
            case $level >= Logger::ERROR:
                return self::COLOR_DANGER;
            case $level >= Logger::WARNING:
                return self::COLOR_WARNING;
            case $level >= Logger::INFO:
                return self::COLOR_GOOD;
            default:
                return self::COLOR_DEFAULT;
        }
    }

    private function generateAttachmentField($title, $value)
    {
        $value = is_array($value) ? sprintf('```%s```', $this->stringify($value)) : $value;
        return array('title' => $title, 'value' => $value, 'short' => false);
    }

    public function stringify($fields)
    {
        $normalized = $this->normalizerFormatter->format($fields);
        $prettyPrintFlag = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 128;
        $hasSecondDimension = count(array_filter($normalized, 'is_array'));
        $hasNonNumericKeys = !count(array_filter(array_keys($normalized), 'is_numeric'));
        return $hasSecondDimension || $hasNonNumericKeys ? json_encode($normalized, $prettyPrintFlag) : json_encode($normalized);
    }

    private function generateAttachmentFields(array $data)
    {
        $fields = array();
        foreach ($data as $key => $value) {
            $fields[] = $this->generateAttachmentField($key, $value);
        }
        return $fields;
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }
}
