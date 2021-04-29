<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Address as BraintreeAddress;
use InvalidArgumentException;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Address\State;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\PaymentMethod\Token;
use Throwable;

class PaymentMethodMapper
{
    /**
     * @return mixed[]
     *
     * @throws InvalidArgumentException
     */
    public function toRequest(PaymentMethod $paymentMethod): array
    {
        if (!$paymentMethod instanceof Nonce) {
            throw new InvalidArgumentException('Expected PaymentMethod of type '.Nonce::class.'. Instead received instance of '.get_class($paymentMethod));
        }

        $request = Arr::replaceKeys($paymentMethod->toArray(), ['nonce' => 'paymentMethodNonce']);
        $request = Arr::assocIn($request, ['options'], ['verifyCard' => true, 'makeDefault' => $paymentMethod->isDefaultPaymentMethod()]);

        return Arr::updateIn($request, ['billingAddress'], function ($address) {
            if (empty($address)) {
                return $address;
            }

            return Arr::replaceKeys($address, ['state' => 'region']);
        });
    }

    /**
     * Map a Braintree payment method to a Dues type. Input is left
     * intentionally as type "mixed" to account for Braintree's lack of a common
     * payment method base.
     *
     * @param mixed $paymentMethod
     */
    public function fromResult($paymentMethod): Token
    {
        $token = new Token($paymentMethod->token);
        $isDefault = $paymentMethod->isDefault();
        $token->setIsDefaultPaymentMethod($isDefault);
        $token->setCustomer(new Customer($paymentMethod->customerId));

        $billingAddress = $paymentMethod->billingAddress;
        if ($billingAddress instanceof BraintreeAddress) {
            $state = null;
            try {
                $state = new State($billingAddress->region);
            } catch (Throwable $e) {
                // we tried
            }
            $token->setBillingAddress(new Address($state, $billingAddress->postalCode));
        }

        return $token;
    }
}
