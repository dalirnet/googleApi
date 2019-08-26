<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class OrderBy extends GoogleModel
{
    public $fieldName;
    public $orderType;
    public $sortOrder;

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function getOrderType()
    {
        return $this->orderType;
    }

    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }
}
