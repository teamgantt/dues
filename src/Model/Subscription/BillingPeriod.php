<?php

namespace TeamGantt\Dues\Model\Subscription;

use DateTime;

class BillingPeriod
{
    protected DateTime $startDate;

    protected DateTime $endDate;

    public function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }
}
