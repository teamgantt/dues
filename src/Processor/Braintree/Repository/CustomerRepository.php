<?php

namespace TeamGantt\Dues\Processor\Braintree\Repository;

use Braintree\Customer as BraintreeCustomer;
use Braintree\Gateway;
use Exception;
use TeamGantt\Dues\Exception\CustomerNotCreatedException;
use TeamGantt\Dues\Exception\CustomerNotDeletedException;
use TeamGantt\Dues\Exception\CustomerNotUpdatedException;
use TeamGantt\Dues\Exception\InvariantException;
use TeamGantt\Dues\Exception\UnknownException;
use TeamGantt\Dues\Model\Customer;
use TeamGantt\Dues\Model\Customer\CustomerSession;
use TeamGantt\Dues\Model\PaymentMethod;
use TeamGantt\Dues\Processor\Braintree\Mapper\CustomerMapper;

class CustomerRepository
{
    protected CustomerMapper $mapper;

    protected PaymentMethodRepository $paymentMethods;

    private Gateway $braintree;

    public function __construct(
        Gateway $braintree,
        CustomerMapper $mapper,
        PaymentMethodRepository $paymentMethods
    ) {
        $this->braintree = $braintree;
        $this->mapper = $mapper;
        $this->paymentMethods = $paymentMethods;
    }

    public function add(Customer $customer): Customer
    {
        $request = $this->mapper->toRequest($customer);

        $result = $this
            ->braintree
            ->customer()
            ->create($request);

        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'Unknown message';
            throw new CustomerNotCreatedException($message);
        }

        if (!isset($result->customer)) {
            throw new InvariantException('Result has no customer property');
        }

        $newCustomer = $this->mapper->fromResult($result->customer);

        $newMethods = array_reduce(
            $customer->getPaymentMethods(),
            fn (array $r, PaymentMethod $m) => [...$r, $this->paymentMethods->add($m->setCustomer($newCustomer))],
            []
        );

        return $newCustomer->setPaymentMethods($newMethods);
    }

    public function update(Customer $customer): Customer
    {
        if ($customer->isNew()) {
            throw new CustomerNotUpdatedException('Cannot update a new customer');
        }

        // Create any new payment methods
        $allPaymentMethods = $customer->getPaymentMethods();
        $paymentMethods = [];
        foreach ($allPaymentMethods as $paymentMethod) {
            if ($paymentMethod->isNew()) {
                $paymentMethods[] = ($this->paymentMethods->add($paymentMethod))->setIsDefaultPaymentMethod($paymentMethod->isDefaultPaymentMethod());
            } else {
                $paymentMethods[] = $paymentMethod;
            }
        }
        $customer->setPaymentMethods($paymentMethods);

        $id = $customer->getId();

        // Update the user @todo rollback payment methods
        $request = $this->mapper->toRequest($customer);
        $result = $this
            ->braintree
            ->customer()
            ->update($id, $request);

        if (!$result->success) {
            $message = isset($result->message) ? $result->message : 'Unknown message';
            throw new CustomerNotUpdatedException($message);
        }

        $customer = $this->find($id);
        if (null === $customer) {
            throw new UnknownException('Could not find updated customer');
        }

        return $customer;
    }

    public function remove(string $id): void
    {
        try {
            $result = $this->braintree->customer()->delete($id);
            if (!$result->success) {
                $message = isset($result->message) ? $result->message : 'Unknown message';
                throw new CustomerNotDeletedException($message);
            }
        } catch (Exception $e) {
            throw new CustomerNotDeletedException($e->getMessage());
        }
    }

    public function find(string $id): ?Customer
    {
        if ($customerResult = $this->findBraintreeCustomer($id)) {
            return $this->mapper->fromResult($customerResult);
        }

        return null;
    }

    public function findBraintreeCustomer(string $customerId): ?BraintreeCustomer
    {
        try {
            $result = $this
                ->braintree
                ->customer()
                ->find($customerId);

            if (is_bool($result)) {
                throw new UnknownException('Customer find request failed');
            }

            return $result;
        } catch (UnknownException $e) {
            throw $e;
        } catch (Exception $e) {
            return null;
        }
    }

    public function createCustomerSession(?string $customerId = null): CustomerSession
    {
        $params = [];

        if (null !== $customerId) {
            $params = ['customerId' => $customerId];
        }

        $id = $this->braintree->clientToken()->generate($params);

        return new CustomerSession($id);
    }
}
