<?php

namespace Google\Service\AnalyticsReporting;

use Google\Api\GoogleCollection;

class SequenceSegment extends GoogleCollection
{
    public $firstStepShouldMatchFirstHit;
    protected $collection_key = 'segmentSequenceSteps';
    protected $segmentSequenceStepsType = 'Google\Service\AnalyticsReporting\SegmentSequenceStep';
    protected $segmentSequenceStepsDataType = 'array';

    public function getFirstStepShouldMatchFirstHit()
    {
        return $this->firstStepShouldMatchFirstHit;
    }

    public function setFirstStepShouldMatchFirstHit($firstStepShouldMatchFirstHit)
    {
        $this->firstStepShouldMatchFirstHit = $firstStepShouldMatchFirstHit;
    }

    public function setSegmentSequenceSteps($segmentSequenceSteps)
    {
        $this->segmentSequenceSteps = $segmentSequenceSteps;
    }

    public function getSegmentSequenceSteps()
    {
        return $this->segmentSequenceSteps;
    }
}
