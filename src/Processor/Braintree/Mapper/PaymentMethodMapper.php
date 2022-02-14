<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Address as BraintreeAddress;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use TeamGantt\Dues\Arr;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Address\Country;
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

            return Arr::replaceKeys($address, [
                'state' => 'region',
                'country' => 'countryCodeAlpha2',
            ]);
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

        if (isset($paymentMethod->expirationMonth) && isset($paymentMethod->expirationYear)) {
            $year = $paymentMethod->expirationYear;
            // expiration dates are good until the last day of the month
            $month = $paymentMethod->expirationMonth + 1;

            if ($expirationDate = DateTime::createFromFormat('Y-m-d h:i:s', $year.'-'.$month.'-01 00:00:00')) {
                // subtract 1 day from the next month to get the last day of the month
                $expirationDate->sub(new DateInterval('P1D'));

                $token->setExpirationDate(DateTimeImmutable::createFromMutable($expirationDate));
            }
        }

        $token->setCustomer(new Customer($paymentMethod->customerId));

        if (!isset($paymentMethod->billingAddress)) {
            return $token;
        }

        $billingAddress = $paymentMethod->billingAddress;
        if ($billingAddress instanceof BraintreeAddress) {
            $state = $this->getStateFromAddress($billingAddress);
            $country = $this->getCountryFromAddress($billingAddress);

            $token->setBillingAddress(new Address($state, $billingAddress->postalCode, $country));
        }

        return $token;
    }

    protected function getStateFromAddress(BraintreeAddress $address): ?State
    {
        try {
            return new State($address->region);
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function getCountryFromAddress(BraintreeAddress $address): ?Country
    {
        try {
            // countryCodeAlpha2 exists but is not documented on the BraintreeAddress
            return new Country($address->countryCodeAlpha2); // @phpstan-ignore-line
        } catch (Throwable $e) {
            return null;
        }
    }
}
