<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class MetricHeaderEntry extends GoogleModel
{
    public $name;
    public $type;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }
}
