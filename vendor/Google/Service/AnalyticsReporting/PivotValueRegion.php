<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class PivotValueRegion extends GoogleCollection
{
    public $values;
    protected $collection_key = 'values';

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }
}
