<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class Dimension extends GoogleCollection
{
    public $histogramBuckets;
    public $name;
    protected $collection_key = 'histogramBuckets';

    public function getHistogramBuckets()
    {
        return $this->histogramBuckets;
    }

    public function setHistogramBuckets($histogramBuckets)
    {
        $this->histogramBuckets = $histogramBuckets;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
