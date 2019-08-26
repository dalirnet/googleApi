<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class DateRange extends GoogleModel
{
    public $endDate;
    public $startDate;

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }
}
