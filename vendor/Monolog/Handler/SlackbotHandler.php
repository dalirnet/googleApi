<?php

namespace Monolog\Handler;

use Monolog\Logger;

class SlackbotHandler extends AbstractProcessingHandler
{
    private $slackTeam;
    private $token;
    private $channel;

    public function __construct($slackTeam, $token, $channel, $level = Logger::CRITICAL, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->slackTeam = $slackTeam;
        $this->token = $token;
        $this->channel = $channel;
    }

    protected function write(array $record)
    {
        $slackbotUrl = sprintf('https://%s.slack.com/services/hooks/slackbot?token=%s&channel=%s', $this->slackTeam, $this->token, $this->channel);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $slackbotUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $record['message']);
        Curl\Util::execute($ch);
    }
}
