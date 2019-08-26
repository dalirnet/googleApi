<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class SegmentDimensionFilter extends GoogleCollection
{
    public $caseSensitive;
    public $dimensionName;
    public $expressions;
    public $maxComparisonValue;
    public $minComparisonValue;
    public $operator;
    protected $collection_key = 'expressions';

    public function getCaseSensitive()
    {
        return $this->caseSensitive;
    }

    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    public function getDimensionName()
    {
        return $this->dimensionName;
    }

    public function setDimensionName($dimensionName)
    {
        $this->dimensionName = $dimensionName;
    }

    public function getExpressions()
    {
        return $this->expressions;
    }

    public function setExpressions($expressions)
    {
        $this->expressions = $expressions;
    }

    public function getMaxComparisonValue()
    {
        return $this->maxComparisonValue;
    }

    public function setMaxComparisonValue($maxComparisonValue)
    {
        $this->maxComparisonValue = $maxComparisonValue;
    }

    public function getMinComparisonValue()
    {
        return $this->minComparisonValue;
    }

    public function setMinComparisonValue($minComparisonValue)
    {
        $this->minComparisonValue = $minComparisonValue;
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
