<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class GetReportsRequest extends GoogleCollection
{
    public $useResourceQuotas;
    protected $collection_key = 'reportRequests';
    protected $reportRequestsType = 'Google\Service\AnalyticsReporting\ReportRequest';
    protected $reportRequestsDataType = 'array';

    public function setReportRequests($reportRequests)
    {
        $this->reportRequests = $reportRequests;
    }

    public function getReportRequests()
    {
        return $this->reportRequests;
    }

    public function getUseResourceQuotas()
    {
        return $this->useResourceQuotas;
    }

    public function setUseResourceQuotas($useResourceQuotas)
    {
        $this->useResourceQuotas = $useResourceQuotas;
    }
}
