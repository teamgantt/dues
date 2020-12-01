<?php

namespace TeamGantt\Dues\Tests\Processor;

use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Processor\Braintree;

class BraintreeTest extends ProcessorTestCase
{
    /**
     * Let's test Dues with Braintree behind the scenes!
     */
    protected function getGateway(): SubscriptionGateway
    {
        return new Braintree([
            'environment' => $_ENV['BRAINTREE_ENVIRONMENT'],
            'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
            'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
            'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY'],
        ]);
    }
}
