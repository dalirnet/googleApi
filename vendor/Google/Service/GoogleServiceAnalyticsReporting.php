<?php

namespace Google\Service;

use Google\Api\GoogleClient;
use Google\Api\GoogleService;
use Google\Service\AnalyticsReporting\Resource\GoogleServiceAnalyticsReportingResourceReports;

class GoogleServiceAnalyticsReporting extends GoogleService
{
    const ANALYTICS = "https://www.googleapis.com/auth/analytics";
    const ANALYTICS_READONLY = "https://www.googleapis.com/auth/analytics.readonly";
    public $reports;

    public function __construct(GoogleClient $client)
    {
        parent::__construct($client);
        $this->rootUrl = 'https://analyticsreporting.googleapis.com/';
        $this->servicePath = '';
        $this->version = 'v4';
        $this->serviceName = 'analyticsreporting';
        $this->reports = new GoogleServiceAnalyticsReportingResourceReports($this, $this->serviceName, 'reports', array('methods' => array('batchGet' => array('path' => 'v4/reports:batchGet', 'httpMethod' => 'POST', 'parameters' => array(),),)));
    }
}
