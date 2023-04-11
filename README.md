# dues

## Table of Contents

- [Goals](#goals)
- [Installation](#installation)
- [Usage](#usage)
- [Development](#development)
- [Testing](#testing)
- [Conventions](#conventions)

### Goals

Dues is meant to provide a high level API for processing payments, particularly subscriptions.

It is designed to be processor agnostic, but currently only supports Braintree.

### Installation

Dues isn't currently published to [packagist](https://packagist.org/). Until it is, it is recommended that dues be
installed from the `main` branch using composer's [vcs](https://getcomposer.org/doc/02-libraries.md#publishing-to-a-vcs) functionality.

Your `composer.json` should include the following:

```json
{
  "repositories": [
    {
      "url": "https://github.com/teamgantt/dues",
      "type": "vcs"
    }
  ],
  "require": {
    "teamgantt/dues": "dev-main",
  }
}
```

See composer documentation for specifying a specific commit.

### Usage

The high level API of dues is accessed by constructing a `Dues` instance with a gateway implementation.

```php
use TeamGantt\Dues\Dues;
use TeamGantt\Dues\Processor\Braintree;

$gateway = new Braintree([
    'environment' => $_ENV['BRAINTREE_ENVIRONMENT'],
    'merchantId' => $_ENV['BRAINTREE_MERCHANT_ID'],
    'publicKey' => $_ENV['BRAINTREE_PUBLIC_KEY'],
    'privateKey' => $_ENV['BRAINTREE_PRIVATE_KEY'],
]);

$dues = new Dues($gateway);
```

All functionality supported by dues can be found on the [`Dues`](src/Dues.php) class.

*Note*: some of dues' public API is mixed in via a [trait](src/Processor/ProcessesSubscriptions.php).

#### Events

Dues provides support for handling certain events via the the `addListener` method. This method expects an implementation of the [`EventListener`](src/Contracts/EventListener.php) interface.

```php
interface EventListener
{
    public function onAfterCreateCustomer(Customer $customer): void;

    public function onAfterUpdateCustomer(Customer $customer): void;

    public function onBeforeCreateCustomer(Customer $customer): void;

    public function onBeforeUpdateCustomer(Customer $customer): void;

    public function onAfterCreateSubscription(Subscription $subscription): void;

    public function onAfterUpdateSubscription(Subscription $subscription): void;

    public function onBeforeCreateSubscription(Subscription $subscription): void;

    public function onBeforeUpdateSubscription(Subscription $subscription): void;
}
```

A listener can be removed by calling `removeEventListener` with the same instance.

```php
$dues->removeListener($listener);
```

Events are useful for logging, analytics, and other integrations (such as dealing with taxes).

### Development

Dues is developed against PHP version 8.1

A [Dockerfile](Dockerfile) is provided for a consistent development environment.

The provided commands in `bin/` assume a docker image named `dues` with a tag of `dev`. You can build this from the root of the repo:

```bash
docker build . -t dues:dev
```

It is recommended, though not necessary, to use [direnv](https://direnv.net/) for development. If direnv is installed, the `.envrc` file will automatically be loaded when entering the project directory. This will allow developers to use the `php` and `composer` commands without having to install them locally. All `composer` commands will be run transparently through the docker container.

#### Executing from the docker container without direnv

You can use the scripts provided in `bin/` directly

```bash
$ bin/composer test
```

### Testing

Dues aims for very high test coverage. Tests are written using [PHPUnit](https://phpunit.de/).

The test suite is broken into unit tests and integration tests.

Individual mileage may vary when running integration tests locally, but the suite is designed to be run in a CI environment. CI tests run against a BrainTree sandbox environment configured by TeamGantt.

To run unit tests locally, run `composer test:unit`.

To run integration tests locally, run `composer test:integration`.

Integration and unit tests are not separated by any kind of file naming convention, but instead they
are grouped together. An integration test should be annotated using a `@group` annotation.

```php
/**
 * @group integration
 */
public function testAProcesserRequest()
{

}
```

Tests are organized by feature in the `tests/Feature` directory.

#### BrainTree tests

In order to ensure a consistent test environment, the Braintree tests are run against a sandbox environment. It is important that "duplicate transaction checking" is disabled. This will allow
integrations to run tests with the same data multiple times without unexpected results.

Please see the Braintree [documentation](https://articles.braintreepayments.com/control-panel/transactions/duplicate-checking#configuring-duplicate-transaction-checking) for more information on configuring duplicate transaction checking.

### Conventions

Dues aims to be consistent in style and type correctness. Code conventions are enforced using [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer). The list of conventions can be found
in the [`.php-cs-fixer.php`](.php-cs-fixer.php) file.

Static analysis is perfomed using [phpstan](https://phpstan.org/). See [phpstan.neon](phpstan.neon) for configuration.

There are composer scripts provided for checking conventions and running static analysis.

```bash
$ composer run fix
$ composer run check
$ composer run analyse
```

*Note:* `composer run fix` will automatically fix any CodeSniffer issues that can be fixed automatically. This is not always possible, so it is recommended that `composer run check` be run before committing code.
