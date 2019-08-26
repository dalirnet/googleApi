<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class ReportRow extends GoogleCollection
{
    public $dimensions;
    protected $collection_key = 'metrics';
    protected $metricsType = 'Google\Service\AnalyticsReporting\DateRangeValues';
    protected $metricsDataType = 'array';

    public function getDimensions()
    {
        return $this->dimensions;
    }

    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    public function setMetrics($metrics)
    {
        $this->metrics = $metrics;
    }

    public function getMetrics()
    {
        return $this->metrics;
    }
}
