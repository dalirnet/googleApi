<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class SegmentFilter extends GoogleModel
{
    public $not;
    protected $sequenceSegmentType = 'Google\Service\AnalyticsReporting\SequenceSegment';
    protected $sequenceSegmentDataType = '';
    protected $simpleSegmentType = 'Google\Service\AnalyticsReporting\SimpleSegment';
    protected $simpleSegmentDataType = '';

    public function getNot()
    {
        return $this->not;
    }

    public function setNot($not)
    {
        $this->not = $not;
    }

    public function setSequenceSegment(SequenceSegment $sequenceSegment)
    {
        $this->sequenceSegment = $sequenceSegment;
    }

    public function getSequenceSegment()
    {
        return $this->sequenceSegment;
    }

    public function setSimpleSegment(SimpleSegment $simpleSegment)
    {
        $this->simpleSegment = $simpleSegment;
    }

    public function getSimpleSegment()
    {
        return $this->simpleSegment;
    }
}
