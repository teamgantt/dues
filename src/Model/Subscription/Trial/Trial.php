<?php

namespace TeamGantt\Dues\Model\Subscription\Trial;

/**
 * The number of days a trial period should be.
 */
class Trial
{
    protected readonly int $timeframe;
    protected readonly TrialUnit $unit;

    public function __construct(int $timeframe, TrialUnit $unit)
    {
        $this->timeframe = $timeframe;
        $this->unit = $unit;
    }

    public function getTimeframe(): int
    {
        return $this->timeframe;
    }

    public function getUnit(): TrialUnit
    {
        return $this->unit;
    }

    /**
     * @return array{'timeframe': int, 'unit': TrialUnit}
     */
    public function toArray(): array
    {
        return [
            'timeframe' => $this->getTimeframe(),
            'unit' => $this->getUnit(),
        ];
    }
}
