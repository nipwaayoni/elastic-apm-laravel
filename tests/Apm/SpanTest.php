<?php

namespace Nipwaayoni\ElasticApmLaravel\Tests\Apm;

use Nipwaayoni\ElasticApmLaravel\Apm\Span;
use Nipwaayoni\ElasticApmLaravel\Apm\SpanCollection;
use Nipwaayoni\ElasticApmLaravel\Tests\TestCase;
use Nipwaayoni\Helper\Timer;

class SpanTest extends TestCase
{
    /** @var Span  */
    private $span;

    public function setUp(): void
    {
        parent::setUp();

        $this->spans = new SpanCollection();
        $this->timer = new Timer();
        $this->timer->start();

        $this->span = new Span($this->timer, $this->spans);
    }

    public function testAddsSelfToCollectionWhenEnded(): void
    {
        $this->assertTrue($this->spans->isEmpty());

        $this->span->end();

        $this->assertCount(1, $this->spans);

        $this->assertSpanAddedWithName('Transaction Span');
        $this->assertSpanAddedWithType('app.span');
    }

    public function testUsesSpecifiedSpanName(): void
    {
        $this->span->end();

        $this->assertSpanAddedWithName('Transaction Span');
    }

    public function testUsesSpecifiedSpanType(): void
    {
        $this->span->end();

        $this->assertSpanAddedWithType('app.span');
    }

    public function testSpanDataAddedToCollection(): void
    {
        $this->span->end();

        $spanData = $this->spans[0];

        $this->assertCount(4, array_keys($spanData));
        $this->assertArrayHasKey('name', $spanData);
        $this->assertArrayHasKey('type', $spanData);
        $this->assertArrayHasKey('start', $spanData);
        $this->assertArrayHasKey('duration', $spanData);
    }
}
