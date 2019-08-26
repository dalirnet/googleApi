<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class Segment extends GoogleModel
{
    public $segmentId;
    protected $dynamicSegmentType = 'Google\Service\AnalyticsReporting\DynamicSegment';
    protected $dynamicSegmentDataType = '';

    public function setDynamicSegment(DynamicSegment $dynamicSegment)
    {
        $this->dynamicSegment = $dynamicSegment;
    }

    public function getDynamicSegment()
    {
        return $this->dynamicSegment;
    }

    public function getSegmentId()
    {
        return $this->segmentId;
    }

    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
    }
}
