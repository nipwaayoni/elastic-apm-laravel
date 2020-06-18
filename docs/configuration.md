# Configuration

The following environment variables are supported in the default configuration:

| Variable          | Description |
|-------------------|-------------|
|APM_ACTIVE         | `true` or `false` defaults to `true`. If `false`, the agent will collect, but not send, transaction data. |
|APM_APPNAME        | Name of the app as it will appear in APM. |
|APM_APPVERSION     | Version of the app as it will appear in APM. |
|APM_SERVERURL      | URL to the APM intake service. |
|APM_SECRETTOKEN    | Secret token, if required. |
|APM_APIVERSION     | APM API version, defaults to `v2` (only v2 is supported at this time). |
|APM_USEROUTEURI    | `true` or `false` defaults to `false`. The default behavior is to record the URL as sent in the request. This can result in excessive unique entries in APM. Set to `true` to have the agent use the route URL instead. |
|APM_QUERYLOG       | `true` or `false` defaults to 'true'. Set to `false` to completely disable query logging, or to `auto` if you would like to use the threshold feature. |
|APM_THRESHOLD      | Query threshold in milliseconds, defaults to `200`. If a query takes longer then 200ms, we enable the query log. Make sure you set `APM_QUERYLOG=auto`. |
|APM_BACKTRACEDEPTH | Defaults to `25`. Depth of backtrace in query span. |
|APM_RENDERSOURCE   | Defaults to `true`. Include source code in query span. |

You may also publish the `elastic-apm.php` configuration file to change additional settings:

```bash
php artisan vendor:publish --tag=config
```

Once published, open the `config/elastic-apm.php` file and review the various settings.

## HTTP Client Configuration

If you need to customize the HTTP client, you must create a [PSR-18](https://www.php-fig.org/psr/psr-18/) compatible implementation and bind it in the Laravel service container. For now, we will use a GuzzleHttp adapter from the PHP-HTTP project.

```bash
composer require http-interop/http-factory-guzzle php-http/guzzle6-adapter
```

The following example demonstrates how to create a GuzzleHttp client that will not verify server certificates. Once you create the client, bind it in the service container under the `ElasticApmHttpClient` abstract.

```php
$this->app->bind('ElasticApmHttpClient', function () {
    // Create the configured client
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        // other client options
    ]);
    
    // Wrap the client object in the adapter and return it
    return new \Http\Adapter\Guzzle6\Client($client);
});

```

If the service container has a binding for `ElasticApmHttpClient`, the concrete implementation will be retrieved and passed into the `Agent`.
