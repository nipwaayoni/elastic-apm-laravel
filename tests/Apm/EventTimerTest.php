<?php

namespace Nipwaayoni\ElasticApmLaravel\Tests\Apm;

use Nipwaayoni\Agent;
use Nipwaayoni\ElasticApmLaravel\Apm\EventTimer;
use Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction;
use Nipwaayoni\ElasticApmLaravel\Tests\TestCase;
use Nipwaayoni\Events\Transaction;
use PHPUnit\Framework\MockObject\MockObject;

class EventTimerTest extends TestCase
{
    /** @var EventTimer  */
    private $eventTimer;

    /** @var Agent|MockObject  */
    private $agent;

    /** @var Transaction|MockObject  */
    private $transaction;

    public function setUp(): void
    {
        parent::setUp();

        $this->agent = $this->createMock(Agent::class);
        $this->transaction = $this->createMock(Transaction::class);
        $this->transaction->method('getId')->willReturn('123');
        $this->transaction->method('getTraceId')->willReturn('abc');

        RecordTransaction::setTransactionName('GET /hello-world');

        $this->eventTimer = new EventTimer($this->agent);
    }

    public function testGetsCurrentTransactionEventWhenParentIsNotProvided(): void
    {
        $this->agent->expects($this->once())->method('getTransaction')
            ->willReturn($this->transaction);

        $this->eventTimer->begin('TestSpan');
    }

    public function testDoesNotGetCurrentTransactionWhenParentIsProvided(): void
    {
        $this->agent->expects($this->never())->method('getTransaction');

        $this->eventTimer->begin('TestSpan', $this->transaction);
    }

    public function testAddsSpanEventToAgentWhenFinished(): void
    {
        $this->agent->expects($this->once())->method('putEvent');

        $span = $this->eventTimer->begin('TestSpan', $this->transaction);
        $this->eventTimer->finish($span);
    }
}
