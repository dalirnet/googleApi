<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class MetricFilter extends GoogleModel
{
    public $comparisonValue;
    public $metricName;
    public $not;
    public $operator;

    public function getComparisonValue()
    {
        return $this->comparisonValue;
    }

    public function setComparisonValue($comparisonValue)
    {
        $this->comparisonValue = $comparisonValue;
    }

    public function getMetricName()
    {
        return $this->metricName;
    }

    public function setMetricName($metricName)
    {
        $this->metricName = $metricName;
    }

    public function getNot()
    {
        return $this->not;
    }

    public function setNot($not)
    {
        $this->not = $not;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
}
