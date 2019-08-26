<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class DateRangeValues extends GoogleCollection
{
    public $values;
    protected $collection_key = 'values';
    protected $pivotValueRegionsType = 'Google\Service\AnalyticsReporting\PivotValueRegion';
    protected $pivotValueRegionsDataType = 'array';

    public function setPivotValueRegions($pivotValueRegions)
    {
        $this->pivotValueRegions = $pivotValueRegions;
    }

    public function getPivotValueRegions()
    {
        return $this->pivotValueRegions;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }
}
