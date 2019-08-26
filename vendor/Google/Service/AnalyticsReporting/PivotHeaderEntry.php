<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class PivotHeaderEntry extends GoogleCollection
{
    public $dimensionNames;
    public $dimensionValues;
    protected $collection_key = 'dimensionValues';
    protected $metricType = 'Google\Service\AnalyticsReporting\MetricHeaderEntry';
    protected $metricDataType = '';

    public function getDimensionNames()
    {
        return $this->dimensionNames;
    }

    public function setDimensionNames($dimensionNames)
    {
        $this->dimensionNames = $dimensionNames;
    }

    public function getDimensionValues()
    {
        return $this->dimensionValues;
    }

    public function setDimensionValues($dimensionValues)
    {
        $this->dimensionValues = $dimensionValues;
    }

    public function setMetric(MetricHeaderEntry $metric)
    {
        $this->metric = $metric;
    }

    public function getMetric()
    {
        return $this->metric;
    }
}
