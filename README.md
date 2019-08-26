# googleApi

Google API client without composer
---
Api added:
+ AnalyticsReporting
+ coming soon...
---
Use:
1. load all resource
```php
include "vendor/autoload.php";
```

2. use namespace
```php
use Google\Api\GoogleClient;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\ReportRequest;
use Google\Service\GoogleServiceAnalyticsReporting;
```

3. demo class
```php
class MyAnalyticsReport
{
    const APPLICATION_NAME = "MyReportApp";
    private $result = [];
    private $viewId;
    private $client;
    private $service;
    private $dateRange;
    private $metric = [];
    private $request;
    private $body;
    private $reports;
    private $scope = ["https://www.googleapis.com/auth/analytics.readonly"];

    private function client($authConfigPath)
    {
        $this->client = new GoogleClient();
        $this->client->setApplicationName(self::APPLICATION_NAME);
        $this->client->setAuthConfig($authConfigPath);
        $this->client->setScopes($this->scope);
    }

    private function service($viewId)
    {
        $this->viewId = $viewId;
        $this->service = new GoogleServiceAnalyticsReporting($this->client);
    }

    private function addDateRange($startDate = "today", $endDate = "today")
    {
        $this->dateRange = new DateRange();
        $this->dateRange->setStartDate($startDate);
        $this->dateRange->setEndDate($endDate);
    }

    private function addMetric($expression, $alias)
    {
        $this->metric[$alias] = new Metric();
        $this->metric[$alias]->setExpression($expression);
        $this->metric[$alias]->setAlias($alias);
    }

    private function request()
    {
        $this->request = new ReportRequest();
        $this->request->setViewId($this->viewId);
        $this->request->setDateRanges($this->dateRange);
        $this->request->setMetrics(array_values($this->metric));
        // get response
        $this->body = new GetReportsRequest();
        $this->body->setReportRequests([$this->request]);
        $this->reports = $this->service->reports->batchGet($this->body);
        // parse response
        for ($reportIndex = 0; $reportIndex < count($this->reports); $reportIndex++) {
            $report = $this->reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $this->result[$entry->getName()] = $values[$k];
                    }
                }
            }
        }
        return $this->result;
    }

    public function create()
    {
        // google api client
        $this->client(getenv("authConfigPath"));
        // add analytics reporting service
        $this->service("149322903");
        // add data range
        $this->addDateRange("today", "today");
        // add metric
        $this->addMetric("ga:impressions", "impressions");
        $this->addMetric("ga:adClicks", "clicks");
        $this->addMetric("ga:adCost", "cost");
        //
        $result = $this->request();
        echo json_encode($result);
    }
}
```

4. generate demo report
```php
$analyticsAdsReport = new MyAnalyticsReport();
$analyticsAdsReport->create();
```
