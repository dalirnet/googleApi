<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class ReportRequest extends GoogleCollection
{
    public $filtersExpression;
    public $hideTotals;
    public $hideValueRanges;
    public $includeEmptyRows;
    public $pageSize;
    public $pageToken;
    public $samplingLevel;
    public $viewId;
    protected $collection_key = 'segments';
    protected $cohortGroupType = 'Google\Service\AnalyticsReporting\CohortGroup';
    protected $cohortGroupDataType = '';
    protected $dateRangesType = 'Google\Service\AnalyticsReporting\DateRange';
    protected $dateRangesDataType = 'array';
    protected $dimensionFilterClausesType = 'Google\Service\AnalyticsReporting\DimensionFilterClause';
    protected $dimensionFilterClausesDataType = 'array';
    protected $dimensionsType = 'Google\Service\AnalyticsReporting\Dimension';
    protected $dimensionsDataType = 'array';
    protected $metricFilterClausesType = 'Google\Service\AnalyticsReporting\MetricFilterClause';
    protected $metricFilterClausesDataType = 'array';
    protected $metricsType = 'Google\Service\AnalyticsReporting\Metric';
    protected $metricsDataType = 'array';
    protected $orderBysType = 'Google\Service\AnalyticsReporting\OrderBy';
    protected $orderBysDataType = 'array';
    protected $pivotsType = 'Google\Service\AnalyticsReporting\Pivot';
    protected $pivotsDataType = 'array';
    protected $segmentsType = 'Google\Service\AnalyticsReporting\Segment';
    protected $segmentsDataType = 'array';

    public function setCohortGroup(CohortGroup $cohortGroup)
    {
        $this->cohortGroup = $cohortGroup;
    }

    public function getCohortGroup()
    {
        return $this->cohortGroup;
    }

    public function setDateRanges($dateRanges)
    {
        $this->dateRanges = $dateRanges;
    }

    public function getDateRanges()
    {
        return $this->dateRanges;
    }

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

    public function getFiltersExpression()
    {
        return $this->filtersExpression;
    }

    public function setFiltersExpression($filtersExpression)
    {
        $this->filtersExpression = $filtersExpression;
    }

    public function getHideTotals()
    {
        return $this->hideTotals;
    }

    public function setHideTotals($hideTotals)
    {
        $this->hideTotals = $hideTotals;
    }

    public function getHideValueRanges()
    {
        return $this->hideValueRanges;
    }

    public function setHideValueRanges($hideValueRanges)
    {
        $this->hideValueRanges = $hideValueRanges;
    }

    public function getIncludeEmptyRows()
    {
        return $this->includeEmptyRows;
    }

    public function setIncludeEmptyRows($includeEmptyRows)
    {
        $this->includeEmptyRows = $includeEmptyRows;
    }

    public function setMetricFilterClauses($metricFilterClauses)
    {
        $this->metricFilterClauses = $metricFilterClauses;
    }

    public function getMetricFilterClauses()
    {
        return $this->metricFilterClauses;
    }

    public function setMetrics($metrics)
    {
        $this->metrics = $metrics;
    }

    public function getMetrics()
    {
        return $this->metrics;
    }

    public function setOrderBys($orderBys)
    {
        $this->orderBys = $orderBys;
    }

    public function getOrderBys()
    {
        return $this->orderBys;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    public function getPageToken()
    {
        return $this->pageToken;
    }

    public function setPageToken($pageToken)
    {
        $this->pageToken = $pageToken;
    }

    public function setPivots($pivots)
    {
        $this->pivots = $pivots;
    }

    public function getPivots()
    {
        return $this->pivots;
    }

    public function getSamplingLevel()
    {
        return $this->samplingLevel;
    }

    public function setSamplingLevel($samplingLevel)
    {
        $this->samplingLevel = $samplingLevel;
    }

    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    public function getSegments()
    {
        return $this->segments;
    }

    public function getViewId()
    {
        return $this->viewId;
    }

    public function setViewId($viewId)
    {
        $this->viewId = $viewId;
    }
}
