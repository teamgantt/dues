<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Subscription;
use Braintree\Subscription\StatusDetails;
use TeamGantt\Dues\Exception\UnknownStatusException;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\Subscription\Status;
use TeamGantt\Dues\Model\Subscription\StatusHistory;

class StatusHistoryMapper
{
    /**
     * @param StatusDetails[] $results
     *
     * @return StatusHistory[]
     */
    public function fromResults(array $results): array
    {
        return array_map(fn (StatusDetails $details) => $this->fromResult($details), $results);
    }

    public function fromResult(StatusDetails $details): StatusHistory
    {
        return (new StatusHistory($details->timestamp))
            ->setStatus($this->getStatus($details))
            ->setBalance(new Money((float) $details->balance))
            ->setPrice(new Money((float) $details->price))
            ->setPlanId($details->planId);
    }

    private function getStatus(StatusDetails $details): Status
    {
        $status = $details->status;

        switch ($status) {
            case Subscription::ACTIVE:
                return Status::active();
            case Subscription::CANCELED:
                return Status::canceled();
            case Subscription::EXPIRED:
                return Status::expired();
            case Subscription::PAST_DUE:
                return Status::pastDue();
            case Subscription::PENDING:
                return Status::pending();
            default:
                throw new UnknownStatusException("Unknown status of $status received");
        }
    }
}
