<?php

namespace TeamGantt\Dues\Tests\Processor;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use TeamGantt\Dues\Contracts\SubscriptionGateway;
use TeamGantt\Dues\Dues;
use TeamGantt\Dues\Tests\Feature;

/**
 * A base class meant to exercise Dues regardless of the underlying
 * subscription gateway implementation.
 */
abstract class ProcessorTestCase extends TestCase
{
    use Feature\Customer;
    use Feature\PaymentMethod;
    use Feature\Plan;
    use Feature\Subscription;
    use Feature\Transaction;

    protected ?Dues $dues = null;

    protected ?SubscriptionGateway $gateway = null;

    public static function setUpBeforeClass(): void
    {
        $dir = realpath(__DIR__.'/../../');
        $path = realpath($dir.'/.env.test');

        if (!file_exists($path)) {
            return;
        }

        $dotenv = Dotenv::createImmutable($dir, '.env.test');
        $dotenv->load();
    }

    protected function setUp(): void
    {
        $this->gateway = $this->getGateway();
        $this->dues = new Dues($this->gateway);
    }

    abstract protected function getGateway(): SubscriptionGateway;
}
