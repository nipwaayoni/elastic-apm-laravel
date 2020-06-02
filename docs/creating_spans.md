# Creating Spans 

The current event implementation creates a single APM transaction to represent the Laravel Request/Response. You can create APM span events to represent discrete actions within the transaction. The `\Nipwaayoni\ElasticApmLaravel\Apm\EventTimer` class facilitates making spans.

```php
class MyClass
{
    /**
     * @var \Nipwaayoni\ElasticApmLaravel\Apm\EventTimer
     */
    private $eventTimer;
    
    public function __construct(\Nipwaayoni\ElasticApmLaravel\Apm\EventTimer $eventTimer)
    {
        $this->eventTimer = $eventTimer;
    }

    public function runSomeRequest(int $number): MyObject
    {
        $event = $this->eventTimer->begin('Request the data');
        $result = $this->someMethod($number);
        $this->eventTimer->finish($event);

        return new MyObject($result);
    }
}
```

The `EventTimer` makes the transaction the parent event of all spans.
