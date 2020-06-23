<?php

namespace Nipwaayoni\ElasticApmLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Nipwaayoni\Agent;
use Nipwaayoni\ElasticApmLaravel\Exceptions\ElasticApmNoCurrentRouteException;
use Nipwaayoni\Helper\Timer;

class RecordTransaction
{
    /**
     * @var Agent
     */
    protected $agent;
    /**
     * @var Timer
     */
    private $timer;

    private static $transactionName;

    public static function setTransactionName(string $name)
    {
        self::$transactionName = $name;
    }

    public static function getTransactionName()
    {
        return self::$transactionName;
    }

    /**
     * RecordTransaction constructor.
     * @param Agent $agent
     */
    public function __construct(Agent $agent, Timer $timer)
    {
        $this->agent = $agent;
        $this->timer = $timer;
    }

    /**
     * [handle description]
     * @param  Request $request [description]
     * @param  Closure $next [description]
     * @return [type]           [description]
     */
    final public function handle($request, Closure $next)
    {
        self::setTransactionName($this->getTransactionNameFromRequest($request));
        $transaction = $this->agent->startTransaction(
            self::getTransactionName()
        );

        $response = $next($request);

        // Rename the transaction if using route uri (route may only available after next)
        if (config('elastic-apm.transactions.use_route_uri')) {
            try {
                $transaction->setTransactionName($this->getTransactionRouteUri($request));
            } catch (ElasticApmNoCurrentRouteException $e) {
                Log::error('No current route when getting uri');
            }
        }

        $transaction->setResponse($this->response($response));
        $transaction->setMeta($this->metadata($response));
        $transaction->setUserContext($this->userContext($request));
        $transaction->setCustomContext($this->customContext($request, $response));

        foreach (app('query-log') as $query) {
            $span = new \Nipwaayoni\Events\Span($query['name'], $transaction);
            $span->setDuration($query['duration']);
            $span->setCustomContext($query['context']);
            $span->setStacktrace($query['stacktrace']->toArray());

            $this->agent->putEvent($span);
        }

        $transaction->stop($this->timer->getElapsedInMilliseconds());

        $this->agent->send();

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Symfony\Component\HttpFoundation\Response $response
     *
     * @return void
     */
    public function terminate($request, $response)
    {
        try {
            $this->agent->send();
        } catch (\Throwable $t) {
            Log::error($t);
        }
    }

    protected function response(Response $response): array
    {
        return [
            'finished' => true,
            'headers_sent' => true,
            'status_code' => $response->getStatusCode(),
            'headers' => $this->formatHeaders($response->headers->all()),
        ];
    }

    protected function metadata(Response $response): array
    {
        return [
            'result' => $response->getStatusCode(),
            'type' => 'HTTP'
        ];
    }

    protected function userContext(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => optional($user)->id,
            'email' => optional($user)->email,
            'username' => optional($user)->user_name,
            'ip' => $request->ip(),
            'user-agent' => $request->userAgent(),
        ];
    }

    protected function customContext(Request $request, Response $response): array
    {
        return [];
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    protected function formatHeaders(array $headers): array
    {
        return collect($headers)->map(function ($values, $header) {
            return head($values);
        })->toArray();
    }

    /**
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    private function getTransactionNameFromRequest(\Illuminate\Http\Request $request): string
    {
        // fix leading /
        $path = ($request->server->get('REQUEST_URI') == '') ? '/' : $request->server->get('REQUEST_URI');

        return $this->makeTransactionName($request->server->get('REQUEST_METHOD'), $path);
    }

    /**
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    private function getTransactionRouteUri(\Illuminate\Http\Request $request): string
    {
        $route = Route::current();

        if (null === $route) {
            throw new ElasticApmNoCurrentRouteException();
        }

        return $this->makeTransactionName($request->server->get('REQUEST_METHOD'), $route->uri());
    }

    private function makeTransactionName(string $method, string $path): string
    {
        return sprintf("%s %s", $method, $path);
    }
}
