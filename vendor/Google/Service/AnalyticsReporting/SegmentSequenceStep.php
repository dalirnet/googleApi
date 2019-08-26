<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class SegmentSequenceStep extends GoogleCollection
{
    public $matchType;
    protected $collection_key = 'orFiltersForSegment';
    protected $orFiltersForSegmentType = 'Google\Service\AnalyticsReporting\OrFiltersForSegment';
    protected $orFiltersForSegmentDataType = 'array';

    public function getMatchType()
    {
        return $this->matchType;
    }

    public function setMatchType($matchType)
    {
        $this->matchType = $matchType;
    }

    public function setOrFiltersForSegment($orFiltersForSegment)
    {
        $this->orFiltersForSegment = $orFiltersForSegment;
    }

    public function getOrFiltersForSegment()
    {
        return $this->orFiltersForSegment;
    }
}
