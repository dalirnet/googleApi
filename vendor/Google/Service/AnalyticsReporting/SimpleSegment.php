<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class SimpleSegment extends GoogleCollection
{
    protected $collection_key = 'orFiltersForSegment';
    protected $orFiltersForSegmentType = 'Google\Service\AnalyticsReporting\OrFiltersForSegment';
    protected $orFiltersForSegmentDataType = 'array';

    public function setOrFiltersForSegment($orFiltersForSegment)
    {
        $this->orFiltersForSegment = $orFiltersForSegment;
    }

    public function getOrFiltersForSegment()
    {
        return $this->orFiltersForSegment;
    }
}
