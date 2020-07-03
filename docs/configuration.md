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

## Customizing Agent Creation

The `Agent` object used to manage APM events is created using the provided `AgentBuilder` class. You can control many aspects of the `Agent` creation by binding your own implementation into the service container. Some helpful examples are shown below. For more details, refer to the [Elastic APM PHP Agent](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/agent.md) documentation.

### Binding an AgentBuilder

Bind your implementation as a singleton in the service container:

```php
$this->app->bind(AgentBuilder::class, function () {
    $builder = new AgentBuilder();

    // configure the builder

    return $builder;
});
```

Note that the `ElasticApmServiceProvider` will always call the `AgentBuilder::withConfig()` and `AgentBuilder::withEnvData()` methods. You must use the provided `elastic-apm` configuration options to influence those settings.

### HTTP Client Configuration

If you need to customize the HTTP client, you must create a [PSR-18](https://www.php-fig.org/psr/psr-18/) compatible implementation and provide it to the `AgentBuilder. For now, we will use a GuzzleHttp adapter from the PHP-HTTP project.

```bash
composer require http-interop/http-factory-guzzle php-http/guzzle6-adapter
```

The following example demonstrates how to create a GuzzleHttp client that will not verify server certificates.

```php
$this->app->bind(AgentBuilder::class, function () {
    $builder = new AgentBuilder();

    $client = new \GuzzleHttp\Client([
        'verify' => false,
        // other client options
    ]);
    
    // Wrap the client object in the adapter and return it
    $builder->withHttpClient(new \Http\Adapter\Guzzle6\Client($client));

    return $builder;
});
```

### APM Transaction Hooks

You can hook the APM HTTP request/response process to examine the data to be sent to APM and the response after sending. This may be helpful in troubleshooting issues.

```php
$this->app->bind(AgentBuilder::class, function () {
    $builder = new AgentBuilder();

    $builder->withPreCommitCallback(function (RequestInterface $request) {
        Log::info(sprintf('Pre commit url is: %s', $request->getUri()));
    });

    $builder->withPostCommitCallback(function (ResponseInterface $response) {
        Log::info(sprintf('Post commit response status: %s', $response->getStatusCode()));
        Log::debug($response->getBody()->getContents());
    });

    return $builder;
});
```