<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleModel;

class Metric extends GoogleModel
{
    public $alias;
    public $expression;
    public $formattingType;

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function setExpression($expression)
    {
        $this->expression = $expression;
    }

    public function getFormattingType()
    {
        return $this->formattingType;
    }

    public function setFormattingType($formattingType)
    {
        $this->formattingType = $formattingType;
    }
}
