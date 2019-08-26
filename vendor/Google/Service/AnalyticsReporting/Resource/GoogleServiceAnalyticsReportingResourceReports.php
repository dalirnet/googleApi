<?php

namespace Google\Service\AnalyticsReporting\Resource;

use Google\Api\Service\GoogleServiceResource;
use Google\Service\AnalyticsReporting\GetReportsRequest;

class GoogleServiceAnalyticsReportingResourceReports extends GoogleServiceResource
{
    public function batchGet(GetReportsRequest $postBody, $optParams = array())
    {
        $params = array('postBody' => $postBody);
        $params = array_merge($params, $optParams);
        return $this->call('batchGet', array($params), "Google\Service\AnalyticsReporting\GetReportsResponse");
    }
}
