<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class OrFiltersForSegment extends GoogleCollection
{
    protected $collection_key = 'segmentFilterClauses';
    protected $segmentFilterClausesType = 'Google\Service\AnalyticsReporting\SegmentFilterClause';
    protected $segmentFilterClausesDataType = 'array';

    public function setSegmentFilterClauses($segmentFilterClauses)
    {
        $this->segmentFilterClauses = $segmentFilterClauses;
    }

    public function getSegmentFilterClauses()
    {
        return $this->segmentFilterClauses;
    }
}
