<?php

namespace TeamGantt\Dues\Processor\Braintree;

use Braintree;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Customer\CustomerBuilder;
use TeamGantt\Dues\Model\PaymentMethod\Token;

class CustomerMapper
{
    private PaymentMethodMapper $paymentMethodMapper;

    public function __construct(PaymentMethodMapper $paymentMethodMapper)
    {
        $this->paymentMethodMapper = $paymentMethodMapper;
    }

    /**
     * @return mixed[]
     */
    public function toRequest(Customer $customer): array
    {
        $request = Arr::replaceKeys($customer->toArray(), ['emailAddress' => 'email']);
        unset($request['id']);
        $defaultPayment = $customer->getDefaultPaymentMethod();
        if ($defaultPayment instanceof Token) {
            $request['defaultPaymentMethodToken'] = $defaultPayment->getValue();
        }

        return $request;
    }

    public function fromResult(Braintree\Customer $result): Customer
    {
        $builder = new CustomerBuilder();

        $customer = $builder
            ->withId($result->id)
            ->withEmailAddress($result->email)
            ->withFirstName($result->firstName)
            ->withLastName($result->lastName)
            ->build();

        foreach ($result->paymentMethods as $paymentMethod) {
            $duesPaymentMethod = $this->paymentMethodMapper->fromResult($paymentMethod);
            $customer->addPaymentMethod($duesPaymentMethod);
        }

        return $customer;
    }
}
