<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class MetricFilterClause extends GoogleCollection
{
    public $operator;
    protected $collection_key = 'filters';
    protected $filtersType = 'Google\Service\AnalyticsReporting\MetricFilter';
    protected $filtersDataType = 'array';

    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    public function getFilters()
    {
        return $this->filters;
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
