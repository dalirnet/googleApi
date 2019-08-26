<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class ReportData extends GoogleCollection
{
    public $dataLastRefreshed;
    public $isDataGolden;
    public $rowCount;
    public $samplesReadCounts;
    public $samplingSpaceSizes;
    protected $collection_key = 'totals';
    protected $maximumsType = 'Google\Service\AnalyticsReporting\DateRangeValues';
    protected $maximumsDataType = 'array';
    protected $minimumsType = 'Google\Service\AnalyticsReporting\DateRangeValues';
    protected $minimumsDataType = 'array';
    protected $rowsType = 'Google\Service\AnalyticsReporting\ReportRow';
    protected $rowsDataType = 'array';
    protected $totalsType = 'Google\Service\AnalyticsReporting\DateRangeValues';
    protected $totalsDataType = 'array';

    public function getDataLastRefreshed()
    {
        return $this->dataLastRefreshed;
    }

    public function setDataLastRefreshed($dataLastRefreshed)
    {
        $this->dataLastRefreshed = $dataLastRefreshed;
    }

    public function getIsDataGolden()
    {
        return $this->isDataGolden;
    }

    public function setIsDataGolden($isDataGolden)
    {
        $this->isDataGolden = $isDataGolden;
    }

    public function setMaximums($maximums)
    {
        $this->maximums = $maximums;
    }

    public function getMaximums()
    {
        return $this->maximums;
    }

    public function setMinimums($minimums)
    {
        $this->minimums = $minimums;
    }

    public function getMinimums()
    {
        return $this->minimums;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function setRowCount($rowCount)
    {
        $this->rowCount = $rowCount;
    }

    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function getSamplesReadCounts()
    {
        return $this->samplesReadCounts;
    }

    public function setSamplesReadCounts($samplesReadCounts)
    {
        $this->samplesReadCounts = $samplesReadCounts;
    }

    public function getSamplingSpaceSizes()
    {
        return $this->samplingSpaceSizes;
    }

    public function setSamplingSpaceSizes($samplingSpaceSizes)
    {
        $this->samplingSpaceSizes = $samplingSpaceSizes;
    }

    public function setTotals($totals)
    {
        $this->totals = $totals;
    }

    public function getTotals()
    {
        return $this->totals;
    }
}
