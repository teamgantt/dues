<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\PaymentInstrumentType as BraintreePaymentInstrumentType;
use Braintree\Transaction as BraintreeTransaction;
use TeamGantt\Dues\Model\CreditCardType;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Money;
use TeamGantt\Dues\Model\Plan;
use TeamGantt\Dues\Model\Plan\NullPlan;
use TeamGantt\Dues\Model\Subscription;
use TeamGantt\Dues\Model\Transaction;
use TeamGantt\Dues\Model\Transaction\CreditCardDetails;
use TeamGantt\Dues\Model\Transaction\PaymentInstrumentType;
use TeamGantt\Dues\Model\Transaction\PayPalDetails;
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
        $subscription = $result->subscriptionId ? new Subscription((string) $result->subscriptionId) : null;
        $transaction = (new Transaction($result->id, $subscription))
            ->setStatus($this->getStatus($result))
            ->setAmount(new Money(floatval($result->amount)))
            ->setCustomer($this->getCustomer($result))
            ->setCreatedAt($result->createdAt)
            ->setAddOns($this->addOnMapper->fromResults($result->addOns))
            ->setDiscounts($this->discountMapper->fromResults($result->discounts))
            ->setPlan($result->planId ? new Plan($result->planId) : new NullPlan())
            ->setType($this->getType($result))
            ->setPaymentInstrumentType($this->getPaymentInstrumentType($result));

        $this->setPaymentInstrument($transaction, $result);

        return $transaction;
    }

    private function setPaymentInstrument(Transaction $transaction, BraintreeTransaction $result): void
    {
        $instrumentType = $result->paymentInstrumentType;
        switch ($instrumentType) {
            case BraintreePaymentInstrumentType::CREDIT_CARD:
                $creditCardDetails = new CreditCardDetails($this->getCreditCardType($result), $result->creditCardDetails->last4 ?? '');
                $transaction->setCreditCardDetails($creditCardDetails);
                break;
            case BraintreePaymentInstrumentType::PAYPAL_ACCOUNT:
                $payPalDetails = new PayPalDetails($result->paypalDetails->payerEmail ?? '');
                $transaction->setPayPalDetails($payPalDetails);
                break;
            default:
                // Ignore unknown instrument types
        }
    }

    private function getCreditCardType(BraintreeTransaction $result): CreditCardType
    {
        $cardType = $result->creditCardDetails->cardType;
        switch ($cardType) {
            case 'American Express':
                return CreditCardType::americanExpress();
            case 'Carte Blanche':
                return CreditCardType::carteBlanche();
            case 'China UnionPay':
                return CreditCardType::chinaUnionPay();
            case 'Discover':
                return CreditCardType::discover();
            case 'Elo':
                return CreditCardType::elo();
            case 'JCB':
                return CreditCardType::jcb();
            case 'Laser':
                return CreditCardType::laser();
            case 'Maestro':
                return CreditCardType::maestro();
            case 'MasterCard':
                return CreditCardType::masterCard();
            case 'Solo':
                return CreditCardType::solo();
            case 'Switch':
                return CreditCardType::switch();
            case 'UK Maestro':
                return CreditCardType::ukMaestro();
            case 'Visa':
                return CreditCardType::visa();
            default:
                return CreditCardType::unknown();
        }
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

    private function getPaymentInstrumentType(BraintreeTransaction $result): PaymentInstrumentType
    {
        $instrumentType = $result->paymentInstrumentType;
        switch ($instrumentType) {
            case BraintreePaymentInstrumentType::CREDIT_CARD:
                return PaymentInstrumentType::creditCard();
            case BraintreePaymentInstrumentType::PAYPAL_ACCOUNT:
                return PaymentInstrumentType::paypalAccount();
            default:
                return PaymentInstrumentType::unknown();
        }
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
