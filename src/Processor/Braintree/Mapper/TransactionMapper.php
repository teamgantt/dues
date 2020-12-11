<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Transaction as BraintreeTransaction;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Model\Transaction\Status;
use TeamGantt\Dues\Model\Transaction\Type;

class TransactionMapper
{
    private AddOnMapper $addOnMapper;

    private DiscountMapper $discountMapper;

    public function __construct(
        AddOnMapper $addOnMapper,
        DiscountMapper $discountMapper
    ) {
        $this->addOnMapper = $addOnMapper;
        $this->discountMapper = $discountMapper;
    }

    public function fromResult(BraintreeTransaction $result): Transaction
    {
        $subscription = new Subscription($result->subscriptionId);
        $transaction = new Transaction($result->id, $subscription);

        return $transaction
            ->setStatus($this->getStatus($result))
            ->setAmount(new Money(floatval($result->amount)))
            ->setCustomer($this->getCustomer($result))
            ->setCreatedAt($result->createdAt)
            ->setAddOns($this->addOnMapper->fromResults($result->addOns))
            ->setDiscounts($this->discountMapper->fromResults($result->discounts))
            ->setPlan(new Plan($result->planId))
            ->setType($this->getType($result));
    }

    private function getCustomer(BraintreeTransaction $result): Customer
    {
        $details = $result->customerDetails;
        $customer = new Customer($details->id);

        return $customer
            ->setEmailAddress((string) $details->email)
            ->setFirstName((string) $details->firstName)
            ->setLastName((string) $details->lastName);
    }

    private function getType(BraintreeTransaction $result): Type
    {
        if (BraintreeTransaction::SALE === $result->type) {
            return Type::sale();
        }

        return Type::credit();
    }

    private function getStatus(BraintreeTransaction $result): Status
    {
        $status = $result->status;
        switch ($status) {
            case BraintreeTransaction::AUTHORIZATION_EXPIRED:
                return Status::authorizationExpired();
            case BraintreeTransaction::AUTHORIZED:
                return Status::authorized();
            case BraintreeTransaction::AUTHORIZING:
                return Status::authorizing();
            case BraintreeTransaction::SETTLEMENT_PENDING:
                return Status::settlementPending();
            case BraintreeTransaction::SETTLEMENT_DECLINED:
                return Status::settlementDeclined();
            case BraintreeTransaction::FAILED:
                return Status::failed();
            case BraintreeTransaction::GATEWAY_REJECTED:
                return Status::gatewayRejected();
            case BraintreeTransaction::PROCESSOR_DECLINED:
                return Status::processorDeclined();
            case BraintreeTransaction::SETTLED:
                return Status::settled();
            case BraintreeTransaction::SETTLING:
                return Status::settling();
            case BraintreeTransaction::SUBMITTED_FOR_SETTLEMENT:
                return Status::submittedForSettlement();
            case BraintreeTransaction::VOIDED:
                return Status::voided();
            case BraintreeTransaction::SETTLEMENT_CONFIRMED:
                return Status::settlementConfirmed();
            default:
                return Status::unrecognized();
        }
    }
}
