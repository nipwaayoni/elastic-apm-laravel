<?php

namespace Nipwaayoni\ElasticApmLaravel\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\Agent;
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
    public function handle($request, Closure $next)
    {
        self::setTransactionName($this->getTransactionNameFromRequest($request));
        $transaction = $this->agent->startTransaction(
            self::getTransactionName()
        );

        // await the outcome
        $response = $next($request);

        $transaction->setResponse([
            'finished' => true,
            'headers_sent' => true,
            'status_code' => $response->getStatusCode(),
            'headers' => $this->formatHeaders($response->headers->all()),
        ]);

        $user = $request->user();
        $transaction->setUserContext([
            'id' => optional($user)->id,
            'email' => optional($user)->email,
            'username' => optional($user)->user_name,
            'ip' => $request->ip(),
            'user-agent' => $request->userAgent(),
        ]);

        $transaction->setMeta([
            'result' => $response->getStatusCode(),
            'type' => 'HTTP'
        ]);

        foreach (app('query-log') as $query) {
            $span = new \Nipwaayoni\Events\Span($query['name'], $transaction);
            $span->setDuration($query['duration']);
            $span->setCustomContext($query['context']);
            $span->setStacktrace($query['stacktrace']->toArray());

            $this->agent->putEvent($span);
        }

        if (config('elastic-apm.transactions.use_route_uri')) {
            $transaction->setTransactionName($this->getRouteUriTransactionName($request));
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

    /**
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function getTransactionNameFromRequest(\Illuminate\Http\Request $request): string
    {
        // fix leading /
        $path = ($request->server->get('REQUEST_URI') == '') ? '/' : $request->server->get('REQUEST_URI');

        return sprintf(
            "%s %s",
            $request->server->get('REQUEST_METHOD'),
            $path
        );
    }

    /**
     * @param  \Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function getRouteUriTransactionName(\Illuminate\Http\Request $request): string
    {
        $path = ($request->path() === '/') ? '' : $request->path();

        return sprintf(
            "%s /%s",
            $request->server->get('REQUEST_METHOD'),
            $path
        );
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
}
