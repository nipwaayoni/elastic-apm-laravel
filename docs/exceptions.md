# Exceptions

Laravel provides a convenient method for working with application exceptions which we can leverage to send Exceptions to APM. In `app/Exceptions/Handler`, add the following to the `report` method:

```php
ElasticApm::captureThrowable($exception);
```

Make sure to import the facade at the top of your file:

```php
use ElasticApm;
```

The collected Exceptions will be sent when `Agent::send()` is called in the middleware.

Note that previous versions of this package suggested calling `Agent::send()` explicitly in `Handler::report()`. The was related to APM  < 7 intake and is no longer suggested as it may lead to duplicate events.

Note that the Laravel exception handler is only aware of exceptions generated after the Laravel framework is minimally bootstrapped. An error preventing the proper executation of the bootstrap process will not be captured in `Handler::report()`.
