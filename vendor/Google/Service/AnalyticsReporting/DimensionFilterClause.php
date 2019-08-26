<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class DimensionFilterClause extends GoogleCollection
{
    public $operator;
    protected $collection_key = 'filters';
    protected $filtersType = 'Google\Service\AnalyticsReporting\DimensionFilter';
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
