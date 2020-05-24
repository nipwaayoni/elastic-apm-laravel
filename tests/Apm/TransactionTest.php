<?php

namespace Nipwaayoni\ElasticApmLaravel\Tests\Apm;

use Nipwaayoni\ElasticApmLaravel\Apm\Span;
use Nipwaayoni\ElasticApmLaravel\Apm\SpanCollection;
use Nipwaayoni\ElasticApmLaravel\Apm\Transaction;
use Nipwaayoni\ElasticApmLaravel\Tests\TestCase;
use Nipwaayoni\Helper\Timer;

class TransactionTest extends TestCase
{
    /** @var Transaction  */
    private $transaction;

    public function setUp(): void
    {
        parent::setUp();

        $this->spans = new SpanCollection();
        $this->timer = new Timer();
        $this->timer->start();

        $this->transaction = new Transaction($this->spans, $this->timer);
    }

    public function testCanStartNewSpan(): void
    {
        $span = $this->transaction->startNewSpan();

        $span->end();

        $this->assertSpanAddedWithName('Transaction Span');
        $this->assertSpanAddedWithType('app.span');
    }

    public function testCanStartNewSpanWithName(): void
    {
        $span = $this->transaction->startNewSpan('My Span');

        $span->end();

        $this->assertSpanAddedWithName('My Span');
    }

    public function testCanStartNewSpanWithType(): void
    {
        $span = $this->transaction->startNewSpan('My Span', 'my.type');

        $span->end();

        $this->assertSpanAddedWithType('my.type');
    }
}
