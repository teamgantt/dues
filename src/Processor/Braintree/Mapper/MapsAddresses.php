<?php

namespace TeamGantt\Dues\Processor\Braintree\Mapper;

use Braintree\Address as BraintreeAddress;
use Braintree\Transaction\AddressDetails;
use TeamGantt\Dues\Model\Address;
use TeamGantt\Dues\Model\Address\Country;
use TeamGantt\Dues\Model\Address\State;

trait MapsAddresses
{
    /**
     * @param BraintreeAddress|AddressDetails $braintreeAddress
     */
    protected function toAddress($braintreeAddress): Address
    {
        $state = $this->getStateFromAddress($braintreeAddress);
        $country = $this->getCountryFromAddress($braintreeAddress);
        $postalCode = $braintreeAddress->postalCode;
        $streetAddress = $braintreeAddress->streetAddress;

        return new Address($state, $postalCode, $country, $streetAddress);
    }

    /**
     * @param BraintreeAddress|AddressDetails $braintreeAddress
     */
    protected function getStateFromAddress($braintreeAddress): ?State
    {
        try {
            return new State($braintreeAddress->region);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param BraintreeAddress|AddressDetails $braintreeAddress
     */
    protected function getCountryFromAddress($braintreeAddress): ?Country
    {
        try {
            // countryCodeAlpha2 exists but is not documented on the BraintreeAddress
            return new Country($braintreeAddress->countryCodeAlpha2);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
