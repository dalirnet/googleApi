<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class DynamicSegment extends GoogleModel
{
    public $name;
    protected $sessionSegmentType = 'Google\Service\AnalyticsReporting\SegmentDefinition';
    protected $sessionSegmentDataType = '';
    protected $userSegmentType = 'Google\Service\AnalyticsReporting\SegmentDefinition';
    protected $userSegmentDataType = '';

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setSessionSegment(SegmentDefinition $sessionSegment)
    {
        $this->sessionSegment = $sessionSegment;
    }

    public function getSessionSegment()
    {
        return $this->sessionSegment;
    }

    public function setUserSegment(SegmentDefinition $userSegment)
    {
        $this->userSegment = $userSegment;
    }

    public function getUserSegment()
    {
        return $this->userSegment;
    }
}
