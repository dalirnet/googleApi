<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class DimensionFilter extends GoogleCollection
{
    public $caseSensitive;
    public $dimensionName;
    public $expressions;
    public $not;
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
