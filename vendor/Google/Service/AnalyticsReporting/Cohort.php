<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class Cohort extends GoogleModel
{
    public $name;
    public $type;
    protected $dateRangeType = 'Google\Service\AnalyticsReporting\DateRange';
    protected $dateRangeDataType = '';

    public function setDateRange(DateRange $dateRange)
    {
        $this->dateRange = $dateRange;
    }

    public function getDateRange()
    {
        return $this->dateRange;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}
