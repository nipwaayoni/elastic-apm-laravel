# Customizing Transactions

The `RecordTransaction` middleware automatically handles most aspects of recording the Laravel transaction. If the default behavior is not sufficient for your needs, you may extend the middleware and override methods to manage the Transaction data.

## Extending the RecordTransaction Class

Create your custom middleware class in `app/Httpd/Middleware` with the contents:

```php
<?php

namespace App\Http\Middleware;

use Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction;

class CustomRecordTransaction extends RecordTransaction
{
    // Implement custom behavior
}
```

Make sure to register your middle in place of the default in `app/Httpd/Kernel.php`. Do not have both active concurrently.

```php
    protected $middleware = [
        ...
        \App\Http\Middleware\CustomRecordTransaction::class
    ];
```

## Overriding Methods

You can override methods in your custom middleware to influence the data included in the `Transaction`. For example:

```php
class CustomRecordTransaction extends RecordTransaction
{
    protected function userContext(Request $request): array
    {
        $context = parent::userContext($request);

        if (empty($context['username'])) {
            $context['username'] = 'anonymous';
        }

        return $context;
    }
}
```

You may override the following methods to manage context data:

```php
RecordTransaction::response(Response $response): array; 
RecordTransaction::metadata(Response $response): array; 
RecordTransaction::userContext(Request $request): array;
RecordTransaction::customContext(Request $request, Response $response): array;
```

These methods must return an array of key/value pairs suitable for using the associated contexts.
