# PHP Elastic APM for Laravel & Lumen

[![Latest Stable Version](https://poser.pugx.org/nipwaayoni/elastic-apm-laravel/v)](//packagist.org/packages/nipwaayoni/elastic-apm-laravel)
[![Total Downloads](https://poser.pugx.org/nipwaayoni/elastic-apm-laravel/downloads)](//packagist.org/packages/nipwaayoni/elastic-apm-laravel)
[![Latest Unstable Version](https://poser.pugx.org/nipwaayoni/elastic-apm-laravel/v/unstable)](//packagist.org/packages/nipwaayoni/elastic-apm-laravel)
[![Build Status](https://github.com/nipwaayoni/elastic-apm-laravel/workflows/CI/badge.svg)](https://github.com/nipwaayoni/elastic-apm-laravel/actions?query=workflow%3ACI)
[![License](https://poser.pugx.org/nipwaayoni/elastic-apm-laravel/license)](//packagist.org/packages/nipwaayoni/elastic-apm-laravel)

Laravel package of the [nipwaayoni/elastic-apm-php-agent](https://github.com/nipwaayoni/elastic-apm-php-agent) library, automatically handling transactions and errors/exceptions. If using `Illuminate\Support\Facades\Auth` the user Id added to the context.
Tested with Laravel `5.6.*` and the nipwaayoni/elastic-apm-php-agent version `7.1.*`.

This package is a continuation of the excellent work done by [philkra](https://github.com/philkra) at
[philkra/elastic-apm-laravel](https://github.com/philkra/elastic-apm-laravel).

## Install

```
composer require nipwaayoni/elastic-apm-laravel
```

The [nipwaayoni/elastic-apm-php-agent](https://github.com/nipwaayoni/elastic-apm-php-agent) no longer includes and http client. You must ensure a [PSR-18](https://www.php-fig.org/psr/psr-18/) compatible implementation is available. Please see the [agent install guide](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/install.md) for more information.

## Service Provider

### Laravel

If using Laravel >=5.5, registration is done automatically by [package discovery](https://laravel.com/docs/7.x/packages#package-discovery).

This package is not tested or asserted to work with Laravel <5.5.

### Lumen

In `bootstrap/app.php` register `\Nipwaayoni\ElasticApmLaravel\Providers\ElasticApmServiceProvider::class` as service provider:

```php
$app->register(\Nipwaayoni\ElasticApmLaravel\Providers\ElasticApmServiceProvider::class);
```

## Middleware

### Laravel

Register as (e.g.) global middleware to be called with every request. https://laravel.com/docs/5.6/middleware#global-middleware

Register the middleware in `app/Http/Kernel.php`

```php
protected $middleware = [
    // ... more middleware
    \Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction::class,
];
```

#### Customizing Transactions

The provided middleware adds request and response context data to the transaction. If the existing behavior does not suit your needs, you can provide your own middleware by overriding the class. See the documentation on [customizing transactions](docs/customizing_transactions.md) for more information.

### Lumen

In `bootstrap/app.php` register `Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction::class` as middleware:

```php
$app->middleware([
    Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction::class
]);
```

## Events

The Elastic APM service supports a variety of event types. This package currently supports only a subset as described here.

### Transaction Event

The `RecordTransaction` middleware automatically starts a new Transaction for the current HTTP Request. 
All additional events will be descendents of this transaction.

There is currently no provision to manage additional Transaction events, or to handle a non-HTTP Request
based process. We hope to address those issues in a future release.

#### Customizing Transaction Context

While the `RecordTransaction` middleware sets supported APM contexts when the transaction is created, you may wish to customize the contexts based on the completed request/response. This can be done by extending the `RecordTransaction` middleware and overriding context methods as described in the [customizing transactions](docs/customizing_transactions.md) documentation.

#### Excluding Requests

You can exclude requests from being sent to APM by using an `except` list of URI patterns. For example, you may wish to avoid sending requests for the (DebugBar)[https://github.com/barryvdh/laravel-debugbar] resources during development or testing.

Add the desired URI patterns to the `except` key in the `elastic-apm` configuration. Note that you must publish the file as described in the [configuration docs](docs/configuration.md).

If you have extended the `RecordTransaction` middleware, as described in the [customizing transactions](docs/customizing_transactions.md) documentation, you may set the `except` list class member there.

### Span Events

Spans occur within a Transaction. Spans represent events within the Transaction. Queries made through Laravel's 
database layer are automatically added to the Transaction. You can add your own Span events using the `EventTimer` 
class from this package. See the docs for [creating spans](docs/creating_spans.md).

Nested Spans are not supported by this package yet.

### Error Events

The APM service defines exception events as a valid type. Exceptions in your application can be sent to APM in addition to any normal exception handling. See the docs for [exceptions](docs/exceptions.md).

## Agent Configuration

You can use a number of environment settings to influence the behavior of this package. At a minimum, you must set the APM server URL and, if applicable, the secret toke:

| Variable          | Description |
|-------------------|-------------|
|APM_SERVERURL      | URL to the APM intake service. |
|APM_SECRETTOKEN    | Secret token, if required. |

Refer to the [configuration docs](docs/configuration.md) for more information.

### HTTP Client Customization

It is no longer possible to provide HTTP client options through the APM PHP Agent configuration. If you need to customize the HTTP client, you must implement and configure a suitable client object and provide it to the `AgentBuilder`. See the "HTTP Client Configuration" section of the [configuration docs](docs/configuration.md).

## Laravel Test Setup

Laravel provides classes to support running unit and feature tests with PHPUnit. In most cases, you will want to explicitly disable APM during testing since it is enabled by default. Refer to the Laravel documentation for more information (https://laravel.com/docs/5.7/testing).

Because the APM agent checks its active status using a strict boolean type, you must ensure your `APM_ACTIVE` value is a boolean `false` rather than simply a falsy value. The best way to accomplish this is to create an `.env.testing` file and include `APM_ACTIVE=false`, along with any other environment settings required for your tests. This file should be safe to include in your SCM.
