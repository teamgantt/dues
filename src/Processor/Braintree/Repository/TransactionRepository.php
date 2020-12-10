<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use Braintree\TransactionSearch;
use DateTime;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Processor\Braintree\Mapper\TransactionMapper;

class TransactionRepository
{
    protected TransactionMapper $mapper;

    private Gateway $braintree;

    const DATE_FORMAT = 'm/d/Y H:i';

    public function __construct(Gateway $braintree, TransactionMapper $mapper)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
    }

    /**
     * @return Transaction[]
     */
    public function findByCustomerId(string $customerId, ?DateTime $start = null, ?DateTime $end = null): array
    {
        $criteria = [TransactionSearch::customerId()->is($customerId)];

        if (null !== $start) {
            $criteria[] = TransactionSearch::createdAt()->greaterThanOrEqualTo($start->format(self::DATE_FORMAT));
        }

        if (null !== $end) {
            $criteria[] = TransactionSearch::createdAt()->lessThanOrEqualTo($end->format(self::DATE_FORMAT));
        }

        $collection = $this->braintree->transaction()->search($criteria);

        $mapped = [];
        foreach ($collection as $transaction) {
            $mapped[] = $this->mapper->fromResult($transaction);
        }

        return $mapped;
    }
}
