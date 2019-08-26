<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class ColumnHeader extends GoogleCollection
{
    public $dimensions;
    protected $collection_key = 'dimensions';
    protected $metricHeaderType = 'Google\Service\AnalyticsReporting\MetricHeader';
    protected $metricHeaderDataType = '';

    public function getDimensions()
    {
        return $this->dimensions;
    }

    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    public function setMetricHeader(MetricHeader $metricHeader)
    {
        $this->metricHeader = $metricHeader;
    }

    public function getMetricHeader()
    {
        return $this->metricHeader;
    }
}
