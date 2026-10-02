<?php

namespace Tests\Review;

use App\Services\Timeline\{WikimediaClient, WikimediaDiscovery};
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Cache, Http, Log};

class WikimediaReliabilityTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Log::spy();
    }

    private function fails(WikimediaClient $client, $endpoint = WikimediaDiscovery::QUERY_ENDPOINT): void
    {
        try {
            $client->get($endpoint, ['query' => 'SELECT ?item WHERE { ?item wdt:P571 ?date }']);
            $this->fail('Expected Wikimedia failure.');
        } catch (\RuntimeException | ConnectionException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_forbidden_is_logged_and_not_retried()
    {
        Http::fake(['*' => Http::response('Access forbidden', 403)]);
        $this->fails(new WikimediaClient);
        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === 403 && $context['forbidden'] && $context['endpoint'] === WikimediaDiscovery::QUERY_ENDPOINT && $context['error_message'] === 'Access forbidden';
        }))->once();
    }

    public function test_rate_limit_respects_retry_after_and_makes_no_immediate_retry()
    {
        Http::fake(['*' => Http::response(['error' => ['info' => 'Rate limited']], 429, ['Retry-After' => '120'])]);
        $client = new WikimediaClient;
        $this->fails($client);
        $this->fails($client);
        Http::assertSentCount(1);
        $key = 'timeline.wikimedia.cooldown.'.sha1(WikimediaDiscovery::QUERY_ENDPOINT);
        $this->assertGreaterThanOrEqual(time() + 119, Cache::get($key));
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['rate_limited'] && $context['retry_after_seconds'] === 120;
        }))->once();
    }

    public function test_retry_after_http_date_is_respected_even_on_503()
    {
        Http::fake(['*' => Http::response('Busy', 503, ['Retry-After' => gmdate('D, d M Y H:i:s', time() + 90).' GMT'])]);
        $this->fails(new WikimediaClient);
        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === 503 && $context['retry_after_seconds'] >= 89;
        }))->once();
    }

    public function test_temporary_5xx_retries_once_and_can_recover()
    {
        Http::fake(['*' => Http::sequence()->push('Temporary gateway error', 502)->push(['results' => ['bindings' => []]], 200)]);
        $data = (new WikimediaClient)->get(WikimediaDiscovery::QUERY_ENDPOINT, []);
        $this->assertSame(['results' => ['bindings' => []]], $data);
        Http::assertSentCount(2);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === 502 && $context['upstream_server_error'] && $context['attempt'] === 1;
        }))->once();
    }

    public function test_sparql_server_timeout_is_logged_without_repeating_expensive_query()
    {
        Http::fake(['*' => Http::response('java.util.concurrent.TimeoutException: SPARQL query timed out', 504)]);
        $this->fails(new WikimediaClient);
        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === 504 && $context['sparql_timeout'] && $context['query_property'] === 'P571';
        }))->once();
    }

    public function test_client_response_timeout_is_distinguished_from_connection_timeout()
    {
        Http::fake(function () {
            $cause = new ConnectException('cURL error 28: Operation timed out', new Request('GET', WikimediaDiscovery::QUERY_ENDPOINT), null, ['errno' => 28, 'connect_time' => .08]);
            throw new ConnectionException($cause->getMessage(), 0, $cause);
        });
        $this->fails(new WikimediaClient);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === null && $context['request_timeout'] && !$context['connection_timeout'] && $context['connected'] && !$context['sparql_timeout'];
        }))->once();
    }

    public function test_connection_timeout_is_logged()
    {
        Http::fake(function () {
            $cause = new ConnectException('cURL error 28: Connection timed out', new Request('GET', WikimediaDiscovery::QUERY_ENDPOINT), null, ['errno' => 28, 'connect_time' => 0]);
            throw new ConnectionException($cause->getMessage(), 0, $cause);
        });
        $this->fails(new WikimediaClient);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['connection_timeout'] && !$context['connected'];
        }))->once();
    }

    public function test_json_api_error_with_http_200_is_logged_and_cooled_down()
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'maxlag', 'info' => 'Waiting for replica lag']], 200, ['Retry-After' => '5'])]);
        $client = new WikimediaClient;
        $this->fails($client, WikimediaDiscovery::ENTITY_ENDPOINT);
        $this->fails($client, WikimediaDiscovery::ENTITY_ENDPOINT);
        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')->with('Timeline Wikimedia request failed', \Mockery::on(function ($context) {
            return $context['http_status'] === 200 && $context['api_error_code'] === 'maxlag' && $context['rate_limited'];
        }))->once();
    }
}
