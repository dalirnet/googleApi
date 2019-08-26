<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class CohortGroup extends GoogleCollection
{
    public $lifetimeValue;
    protected $collection_key = 'cohorts';
    protected $cohortsType = 'Google\Service\AnalyticsReporting\Cohort';
    protected $cohortsDataType = 'array';

    public function setCohorts($cohorts)
    {
        $this->cohorts = $cohorts;
    }

    public function getCohorts()
    {
        return $this->cohorts;
    }

    public function getLifetimeValue()
    {
        return $this->lifetimeValue;
    }

    public function setLifetimeValue($lifetimeValue)
    {
        $this->lifetimeValue = $lifetimeValue;
    }
}
