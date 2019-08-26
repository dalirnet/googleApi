<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class GetReportsResponse extends GoogleCollection
{
    public $queryCost;
    protected $collection_key = 'reports';
    protected $reportsType = 'Google\Service\AnalyticsReporting\Report';
    protected $reportsDataType = 'array';
    protected $resourceQuotasRemainingType = 'Google\Service\AnalyticsReporting\ResourceQuotasRemaining';
    protected $resourceQuotasRemainingDataType = '';

    public function getQueryCost()
    {
        return $this->queryCost;
    }

    public function setQueryCost($queryCost)
    {
        $this->queryCost = $queryCost;
    }

    public function setReports($reports)
    {
        $this->reports = $reports;
    }

    public function getReports()
    {
        return $this->reports;
    }

    public function setResourceQuotasRemaining(ResourceQuotasRemaining $resourceQuotasRemaining)
    {
        $this->resourceQuotasRemaining = $resourceQuotasRemaining;
    }

    public function getResourceQuotasRemaining()
    {
        return $this->resourceQuotasRemaining;
    }
}
