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

    /**
     * Returns the billing cycle as a number of days. That is the number
     * of days between the billing period start and end date.
     */
    public function getBillingCycle(): int
    {
        return $this->getStartDate()->diff($this->getEndDate())->d;
    }

    /**
     * Returns the number of days left in a billing period. That is
     * the number of days between today and the end of the billing period.
     */
    public function getRemainingBillingCycle(): int
    {
        $today = new DateTime('UTC');

        return $today->diff($this->getEndDate())->d;
    }
}
