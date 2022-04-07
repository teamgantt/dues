<?php

namespace TeamGantt\Dues\Model\Subscription;

use DateTime;
use TeamGantt\Dues\Exception\BillingPeriodCycleException;

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

    /**
     * Returns the billing cycle as a number of days. That is the number
     * of days between the billing period start and end date.
     */
    public function getBillingCycle(): int
    {
        $days = $this->getStartDate()->diff($this->getEndDate())->days;

        if (false === $days) {
            throw new BillingPeriodCycleException('Unable to determine the number of days in the billing cycle.');
        }

        return $days + 1; // add one day to include the start day of the cycle
    }

    /**
     * Returns the number of days left in a billing period. That is
     * the number of days between today and the end of the billing period.
     */
    public function getRemainingBillingCycle(): int
    {
        $today = new DateTime('UTC');
        $remainingDays = $today->diff($this->getEndDate())->days;

        if (false === $remainingDays) {
            throw new BillingPeriodCycleException('Unable to determine the number of days remaining in the current billing cycle.');
        }

        return $remainingDays + 1; // add one day to include today
    }
}
