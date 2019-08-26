<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class Pivot extends GoogleCollection
{
    public $maxGroupCount;
    public $startGroup;
    protected $collection_key = 'metrics';
    protected $dimensionFilterClausesType = 'Google\Service\AnalyticsReporting\DimensionFilterClause';
    protected $dimensionFilterClausesDataType = 'array';
    protected $dimensionsType = 'Google\Service\AnalyticsReporting\Dimension';
    protected $dimensionsDataType = 'array';
    protected $metricsType = 'Google\Service\AnalyticsReporting\Metric';
    protected $metricsDataType = 'array';

    public function setDimensionFilterClauses($dimensionFilterClauses)
    {
        $this->dimensionFilterClauses = $dimensionFilterClauses;
    }

    public function getDimensionFilterClauses()
    {
        return $this->dimensionFilterClauses;
    }

    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    public function getDimensions()
    {
        return $this->dimensions;
    }

    public function getMaxGroupCount()
    {
        return $this->maxGroupCount;
    }

    public function setMaxGroupCount($maxGroupCount)
    {
        $this->maxGroupCount = $maxGroupCount;
    }

    public function setMetrics($metrics)
    {
        $this->metrics = $metrics;
    }

    public function getMetrics()
    {
        return $this->metrics;
    }

    public function getStartGroup()
    {
        return $this->startGroup;
    }

    public function setStartGroup($startGroup)
    {
        $this->startGroup = $startGroup;
    }
}
