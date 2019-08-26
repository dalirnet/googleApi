<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class SegmentFilterClause extends GoogleModel
{
    public $not;
    protected $dimensionFilterType = 'Google\Service\AnalyticsReporting\SegmentDimensionFilter';
    protected $dimensionFilterDataType = '';
    protected $metricFilterType = 'Google\Service\AnalyticsReporting\SegmentMetricFilter';
    protected $metricFilterDataType = '';

    public function setDimensionFilter(SegmentDimensionFilter $dimensionFilter)
    {
        $this->dimensionFilter = $dimensionFilter;
    }

    public function getDimensionFilter()
    {
        return $this->dimensionFilter;
    }

    public function setMetricFilter(SegmentMetricFilter $metricFilter)
    {
        $this->metricFilter = $metricFilter;
    }

    public function getMetricFilter()
    {
        return $this->metricFilter;
    }

    public function getNot()
    {
        return $this->not;
    }

    public function setNot($not)
    {
        $this->not = $not;
    }
}
