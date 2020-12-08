<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Gateway;
use TeamGantt\Dues\Exception\PaymentMethodNotCreatedException;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Processor\Braintree\Mapper\PaymentMethodMapper;

class PaymentMethodRepository
{
    protected PaymentMethodMapper $mapper;

    private Gateway $braintree;

    public function __construct(Gateway $braintree, PaymentMethodMapper $mapper)
    {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
    }

    public function add(PaymentMethod $paymentMethod): Token
    {
        $request = $this->mapper->toRequest($paymentMethod);

        $result = $this
            ->braintree
            ->paymentMethod()
            ->create($request);

        if (!$result->success) {
            throw new PaymentMethodNotCreatedException($result->message);
        }

        return $this->mapper->fromResult($result->paymentMethod);
    }
}
