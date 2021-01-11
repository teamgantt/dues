<?php

namespace TeamGantt\Dues\Processor\Braintree\Subscription\Update;

use Braintree\Result\Error;
use Braintree\Result\Successful;
use Exception;
use TeamGantt\Dues\Exception\SubscriptionNotUpdatedException;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use TeamGantt\Dues\Model\Subscription;

class PastDueUpdateStrategy extends DefaultUpdateStrategy
{
    public function update(Subscription $subscription): ?Subscription
    {
        try {
            // handle delinquent subscription.
            $this->setNewPaymentMethod($subscription);
            $this->retrySubscription($subscription);

            // update the no longer delinquent subscription per changes provided.
            return parent::update($subscription);
        } catch (Exception $e) {
            throw new SubscriptionNotUpdatedException($e->getMessage());
        }
    }

    /**
     * Sets only the new payment method for the existing subscription that is past due.
     */
    private function setNewPaymentMethod(Subscription $subscription): Successful
    {
        $paymentMethod = $subscription->getPaymentMethod();

        if (!($paymentMethod instanceof Token)) {
            throw new SubscriptionNotUpdatedException('A valid payment token must be supplied.');
        }

        $paymentTokenRequest = ['paymentMethodToken' => $paymentMethod->getValue()];

        $updatePaymentMethod = $this
            ->braintree
            ->subscription()
            ->update($subscription->getId(), $paymentTokenRequest);

        if ($updatePaymentMethod instanceof Error) {
            throw new SubscriptionNotUpdatedException('Unable to update the payment method on the current subscription.');
        }

        return $updatePaymentMethod;
    }

    /**
     * After a new payment method has been assigned attempt to bring the subscription to current.
     */
    private function retrySubscription(Subscription $subscription): Successful
    {
        $retrySubscription = $this->braintree->subscription()->retryCharge($subscription->getId());

        if ($retrySubscription instanceof Error) {
            throw new SubscriptionNotUpdatedException('Unable to resolve delinquent subscription before upgrading.');
        }

        return $retrySubscription;
    }
}
