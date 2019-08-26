<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class PivotHeader extends GoogleCollection
{
    public $totalPivotGroupsCount;
    protected $collection_key = 'pivotHeaderEntries';
    protected $pivotHeaderEntriesType = 'Google\Service\AnalyticsReporting\PivotHeaderEntry';
    protected $pivotHeaderEntriesDataType = 'array';

    public function setPivotHeaderEntries($pivotHeaderEntries)
    {
        $this->pivotHeaderEntries = $pivotHeaderEntries;
    }

    public function getPivotHeaderEntries()
    {
        return $this->pivotHeaderEntries;
    }

    public function getTotalPivotGroupsCount()
    {
        return $this->totalPivotGroupsCount;
    }

    public function setTotalPivotGroupsCount($totalPivotGroupsCount)
    {
        $this->totalPivotGroupsCount = $totalPivotGroupsCount;
    }
}
