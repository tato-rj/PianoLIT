<?php

namespace App\Services\Timeline;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Cache, Http, Log};
use Illuminate\Support\Str;

class WikimediaClient
{
    public function get(string $endpoint, array $params, ?float $deadline = null): array
    {
        $queryContext = [];
        if (isset($params['query']) && preg_match('/\?item wdt:(P\d+) \?date/', $params['query'], $matches)) {
            $queryContext['query_property'] = $matches[1];
        }
        $cooldownKey = 'timeline.wikimedia.cooldown.'.sha1($endpoint);
        if (Cache::get($cooldownKey, 0) > time()) {
            throw new \RuntimeException('Wikimedia rate-limit cooldown is active.');
        }
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $remaining = $deadline ? $deadline - microtime(true) : config('wikimedia.timeout');
            if ($remaining < 1) throw new \RuntimeException('Wikimedia discovery request budget exhausted.');
            try {
                $response = Http::withHeaders(['User-Agent' => config('wikimedia.user_agent'), 'Accept' => 'application/json'])
                    ->withOptions(['connect_timeout' => min(3, $remaining)])
                    ->timeout((int) min($remaining, config($endpoint === WikimediaDiscovery::QUERY_ENDPOINT ? 'wikimedia.query_timeout' : 'wikimedia.timeout')))
                    ->get($endpoint, $params);
            } catch (ConnectionException $e) {
                $previous = $e->getPrevious();
                $context = $previous && method_exists($previous, 'getHandlerContext') ? $previous->getHandlerContext() : [];
                $timeout = ($context['errno'] ?? null) === 28 || strpos($e->getMessage(), 'cURL error 28') !== false;
                $connectTime = $context['connect_time'] ?? null;
                // Log the endpoint, not the full Guzzle URL containing a potentially large query.
                $message = preg_replace('/\s+for https?:\/\/\S+/s', '', $e->getMessage());
                $this->log($endpoint, null, $message, array_merge($queryContext, [
                    'connection_error' => true, 'request_timeout' => $timeout,
                    'connection_timeout' => $timeout && $connectTime !== null ? $connectTime <= 0 : null,
                    'connected' => $connectTime !== null ? $connectTime > 0 : null,
                    'query_response_timeout' => $endpoint === WikimediaDiscovery::QUERY_ENDPOINT && $timeout,
                    'sparql_timeout' => false, 'attempt' => $attempt,
                ]));
                // Repeating a timed-out SPARQL query adds load without fixing its execution plan.
                if ($attempt === 2 || $timeout || ($deadline && $deadline - microtime(true) < 2)) {
                    if (!$timeout) Cache::put($cooldownKey, time() + 10, now()->addSeconds(10));
                    throw $e;
                }
                usleep(250000);
                continue;
            }
            $data = $response->json();
            if ($response->successful() && is_array($data) && !isset($data['error'])) return $data;
            $error = is_array($data) ? ($data['error']['info'] ?? $data['error']['code'] ?? '') : '';
            $message = $error ?: $response->body();
            $rateLimited = $response->status() === 429 || in_array($data['error']['code'] ?? '', ['ratelimited', 'maxlag']);
            $retryAfter = $this->retryAfter($response->header('Retry-After'));
            if ($rateLimited || $retryAfter) {
                $delay = max(1, $retryAfter ?: 60);
                Cache::put($cooldownKey, time() + $delay, now()->addSeconds($delay));
            }
            $sparqlTimeout = $endpoint === WikimediaDiscovery::QUERY_ENDPOINT
                && preg_match('/QueryTimeout|TimeoutException|query[^\n]{0,80}(?:timed out|timeout)|deadline/i', $message);
            $this->log($endpoint, $response->status(), $message, array_merge($queryContext, [
                'connection_error' => false, 'connection_timeout' => false, 'request_timeout' => false,
                'sparql_timeout' => (bool) $sparqlTimeout, 'retry_after_seconds' => $retryAfter,
                'api_error_code' => $data['error']['code'] ?? null, 'rate_limited' => $rateLimited,
                'attempt' => $attempt,
            ]));
            if ($attempt === 1 && $response->serverError() && !$rateLimited && !$retryAfter && !$sparqlTimeout
                && (!$deadline || $deadline - microtime(true) >= 2)) {
                usleep(250000);
                continue;
            }
            if ($response->status() === 403 || ($response->serverError() && !$retryAfter)) {
                Cache::put($cooldownKey, time() + 10, now()->addSeconds(10));
            }
            throw new \RuntimeException('Wikimedia request failed.');
        }
        throw new \RuntimeException('Wikimedia request failed.');
    }

    private function retryAfter($header): ?int
    {
        if (!$header) return null;
        if (ctype_digit(trim($header))) return (int) trim($header);
        $date = strtotime($header);
        return $date === false ? null : max(1, $date - time());
    }

    private function log($endpoint, $status, $message, array $context): void
    {
        Log::warning('Timeline Wikimedia request failed', array_merge([
            'endpoint' => $endpoint, 'http_status' => $status,
            'error_message' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($message))), 1500, ''),
            'forbidden' => $status === 403, 'rate_limited' => $status === 429,
            'upstream_server_error' => $status >= 500,
        ], $context));
    }
}
