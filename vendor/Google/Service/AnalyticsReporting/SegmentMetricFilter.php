<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class SegmentMetricFilter extends GoogleModel
{
    public $comparisonValue;
    public $maxComparisonValue;
    public $metricName;
    public $operator;
    public $scope;

    public function getComparisonValue()
    {
        return $this->comparisonValue;
    }

    public function setComparisonValue($comparisonValue)
    {
        $this->comparisonValue = $comparisonValue;
    }

    public function getMaxComparisonValue()
    {
        return $this->maxComparisonValue;
    }

    public function setMaxComparisonValue($maxComparisonValue)
    {
        $this->maxComparisonValue = $maxComparisonValue;
    }

    public function getMetricName()
    {
        return $this->metricName;
    }

    public function setMetricName($metricName)
    {
        $this->metricName = $metricName;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }
}
