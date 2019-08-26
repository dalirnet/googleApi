<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class ResourceQuotasRemaining extends GoogleModel
{
    public $dailyQuotaTokensRemaining;
    public $hourlyQuotaTokensRemaining;

    public function getDailyQuotaTokensRemaining()
    {
        return $this->dailyQuotaTokensRemaining;
    }

    public function setDailyQuotaTokensRemaining($dailyQuotaTokensRemaining)
    {
        $this->dailyQuotaTokensRemaining = $dailyQuotaTokensRemaining;
    }

    public function getHourlyQuotaTokensRemaining()
    {
        return $this->hourlyQuotaTokensRemaining;
    }

    public function setHourlyQuotaTokensRemaining($hourlyQuotaTokensRemaining)
    {
        $this->hourlyQuotaTokensRemaining = $hourlyQuotaTokensRemaining;
    }
}
