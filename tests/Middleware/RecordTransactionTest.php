<?php

namespace Nipwaayoni\ElasticApmLaravel\Tests\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Nipwaayoni\Agent;
use Nipwaayoni\ElasticApmLaravel\Middleware\RecordTransaction;
use Nipwaayoni\ElasticApmLaravel\Tests\TestCase;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Helper\Timer;
use PHPUnit\Framework\MockObject\MockObject;

class RecordTransactionTest extends TestCase
{
    /** @var Agent|MockObject */
    private $agent;
    /** @var Timer|MockObject */
    private $timer;
    /** @var Transaction|MockObject  */
    private $transaction;

    public function setUp(): void
    {
        parent::setup();

        $this->agent = $this->createMock(Agent::class);
        $this->timer = $this->createMock(Timer::class);
        $this->transaction = $this->createMock(Transaction::class);
    }

    protected function useRequestUriForTransactionName($app)
    {
        $app->config->set('elastic-apm.transactions.use_route_uri', false);
    }

    /**
     * @environment-setup useRequestUriForTransactionName
     */
    public function testSetsTransactionNameToHttpUriByDefault(): void
    {
        $this->agent->expects($this->once())->method('startTransaction')
            ->with($this->equalTo('GET /some/path/123?option=red'))
            ->willReturn($this->transaction);

        $this->transaction->expects($this->never())->method('setTransactionName');

        $request = $this->makeRequest('GET', ['server' => ['REQUEST_URI' => '/some/path/123?option=red']]);
        $next = function () {
            return new Response();
        };

        $recorder = new RecordTransaction($this->agent, $this->timer);

        $recorder->handle($request, $next);
    }

    protected function useRouteUriForTransactionName($app)
    {
        $app->config->set('elastic-apm.transactions.use_route_uri', true);
    }

    /**
     * @environment-setup useRouteUriForTransactionName
     */
    public function testSetsTransactionNameToRouteUriWhenConfigured(): void
    {
        $route = $this->createMock(\Illuminate\Routing\Route::class);
        $route->expects($this->once())->method('uri')->willReturn('/some/path/{id}');

        Route::shouldReceive('current')
            ->once()
            ->andReturn($route);

        $this->agent->expects($this->once())->method('startTransaction')
            ->with($this->equalTo('GET /some/path/123?option=red'))
            ->willReturn($this->transaction);

        $this->transaction->expects($this->once())->method('setTransactionName')
            ->with($this->equalTo('GET /some/path/{id}'));

        $request = $this->makeRequest('GET', ['server' => ['REQUEST_URI' => '/some/path/123?option=red']]);
        $next = function () {
            return new Response();
        };

        $recorder = new RecordTransaction($this->agent, $this->timer);

        $recorder->handle($request, $next);
    }

    /**
     * @environment-setup useRouteUriForTransactionName
     */
    public function testSetsTransactionNameToRouteUriLogsErrorWhenWithoutCurrentRoute(): void
    {
        Route::shouldReceive('current')
            ->once()
            ->andReturn(null);

        Log::shouldReceive('error')
            ->once()
            ->with('No current route when getting uri');

        $this->agent->expects($this->once())->method('startTransaction')
            ->with($this->equalTo('GET /some/path/123?option=red'))
            ->willReturn($this->transaction);

        $this->transaction->expects($this->never())->method('setTransactionName');

        $request = $this->makeRequest('GET', ['server' => ['REQUEST_URI' => '/some/path/123?option=red']]);
        $next = function () {
            return new Response();
        };

        $recorder = new RecordTransaction($this->agent, $this->timer);

        $recorder->handle($request, $next);
    }

    protected function makeRequest(string $method = 'GET', array $options = []): Request
    {
        $query = $options['query'] ?? [];
        $request = $options['request'] ?? [];
        $attributes = $options['attributes'] ?? [];
        $cookies = $options['cookies'] ?? [];
        $files = $options['files'] ?? [];
        $server = $options['server'] ?? [];
        $content = $options['content'] ?? null;

        $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);
        $request->setMethod($method);

        return $request;
    }
}
