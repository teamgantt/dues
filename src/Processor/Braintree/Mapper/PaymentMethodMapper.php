<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use InvalidArgumentException;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Model\PaymentMethod\Nonce;
use TeamGantt\Dues\Model\PaymentMethod\Token;

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

        return Arr::replaceKeys($paymentMethod->toArray(), ['nonce' => 'paymentMethodNonce']);
    }

    /**
     * Map a Braintree payment method to a Dues type. Input is left
     * inentionally as type "mixed" to account for Braintree's lack of a common
     * payment method base.
     *
     * @param mixed $paymentMethod
     */
    public function fromResult($paymentMethod): Token
    {
        $token = new Token($paymentMethod->token);
        $isDefault = $paymentMethod->isDefault();
        $token->setIsDefaultPaymentMethod($isDefault);

        return $token;
    }
}
