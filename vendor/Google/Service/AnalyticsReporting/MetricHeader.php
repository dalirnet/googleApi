<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class MetricHeader extends GoogleCollection
{
    protected $collection_key = 'pivotHeaders';
    protected $metricHeaderEntriesType = 'Google\Service\AnalyticsReporting\MetricHeaderEntry';
    protected $metricHeaderEntriesDataType = 'array';
    protected $pivotHeadersType = 'Google\Service\AnalyticsReporting\PivotHeader';
    protected $pivotHeadersDataType = 'array';

    public function setMetricHeaderEntries($metricHeaderEntries)
    {
        $this->metricHeaderEntries = $metricHeaderEntries;
    }

    public function getMetricHeaderEntries()
    {
        return $this->metricHeaderEntries;
    }

    public function setPivotHeaders($pivotHeaders)
    {
        $this->pivotHeaders = $pivotHeaders;
    }

    public function getPivotHeaders()
    {
        return $this->pivotHeaders;
    }
}
