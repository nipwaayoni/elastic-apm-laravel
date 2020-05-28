<?php


namespace Nipwaayoni\ElasticApmLaravel\Apm;

use Nipwaayoni\Events\EventBean;

/**
 * Class Span
 * @package Nipwaayoni\ElasticApmLaravel\Apm
 *
 * This is a simple proxy class to hide the \Nipwaayoni\Events\Span class from
 * outside consumers.
 */
class Span
{
    /** @var \Nipwaayoni\Events\Span  */
    private $span;

    public function __construct(string $name, EventBean $parentEvent = null)
    {
        $this->span = new \Nipwaayoni\Events\Span($name, $parentEvent);
        $this->span->start();
    }

    public function stop(): void
    {
        $this->span->stop();
    }

    public function event(): \Nipwaayoni\Events\Span
    {
        return $this->span;
    }
}
