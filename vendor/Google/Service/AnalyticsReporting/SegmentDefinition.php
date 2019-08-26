<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class SegmentDefinition extends GoogleCollection
{
    protected $collection_key = 'segmentFilters';
    protected $segmentFiltersType = 'Google\Service\AnalyticsReporting\SegmentFilter';
    protected $segmentFiltersDataType = 'array';

    public function setSegmentFilters($segmentFilters)
    {
        $this->segmentFilters = $segmentFilters;
    }

    public function getSegmentFilters()
    {
        return $this->segmentFilters;
    }
}
