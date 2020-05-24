<?php


namespace Nipwaayoni\ElasticApmLaravel\Tests;

use Nipwaayoni\ElasticApmLaravel\Apm\SpanCollection;
use Nipwaayoni\ElasticApmLaravel\Facades\ElasticApm;
use Nipwaayoni\ElasticApmLaravel\Providers\ElasticApmServiceProvider;
use Nipwaayoni\Helper\Timer;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @var SpanCollection  */
    protected $spans;
    /** @var Timer  */
    protected $timer;

    protected function getPackageProviders($app)
    {
        return [ElasticApmServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ElasticApm' => ElasticApm::class
        ];
    }

    protected function assertSpanAddedWithName(string $name, int $index = 0): void
    {
        $this->assertTrue($this->spans->isNotEmpty());

        $spanData = $this->spans[$index];

        $this->assertArrayHasKey('name', $spanData);
        $this->assertEquals($name, $spanData['name']);
    }

    protected function assertSpanAddedWithType(string $type, int $index = 0): void
    {
        $this->assertTrue($this->spans->isNotEmpty());

        $spanData = $this->spans[$index];

        $this->assertArrayHasKey('type', $spanData);
        $this->assertEquals($type, $spanData['type']);
    }
}