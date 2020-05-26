<?php


namespace Nipwaayoni\ElasticApmLaravel\Apm;

use Nipwaayoni\Agent;
use Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction;
use Nipwaayoni\Events\EventBean;

class EventTimer
{
    /**
     * @var Agent
     */
    private $agent;

    private $spans = [];

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function begin(string $name, EventBean $parentEvent = null): Span
    {
        if (null === $parentEvent) {
            $parentEvent = $this->agent->getTransaction(RecordTransaction::getTransactionName());
        }

        $this->spans[$name] = new Span($name, $parentEvent);

        return $this->spans[$name];
    }

    public function finish(Span $span): void
    {
        $span->stop();

        $this->agent->putEvent($span->event());
    }
}
