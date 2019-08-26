<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class Report extends GoogleModel
{
    public $nextPageToken;
    protected $columnHeaderType = 'Google\Service\AnalyticsReporting\ColumnHeader';
    protected $columnHeaderDataType = '';
    protected $dataType = 'Google\Service\AnalyticsReporting\ReportData';
    protected $dataDataType = '';

    public function setColumnHeader(ColumnHeader $columnHeader)
    {
        $this->columnHeader = $columnHeader;
    }

    public function getColumnHeader()
    {
        return $this->columnHeader;
    }

    public function setData(ReportData $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getNextPageToken()
    {
        return $this->nextPageToken;
    }

    public function setNextPageToken($nextPageToken)
    {
        $this->nextPageToken = $nextPageToken;
    }
}
