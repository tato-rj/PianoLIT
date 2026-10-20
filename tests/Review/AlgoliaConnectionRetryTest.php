<?php

namespace Tests\Review;

use Algolia\AlgoliaSearch\{Algolia, SearchClient};
use Algolia\AlgoliaSearch\Config\SearchConfig;
use Algolia\AlgoliaSearch\Http\Guzzle6HttpClient;
use Algolia\AlgoliaSearch\RetryStrategy\{ApiWrapper, ClusterHosts};
use App\Services\Search\AlgoliaHttpClient;
use GuzzleHttp\{Client, HandlerStack, Middleware};
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\{Request, Response};
use Psr\Log\NullLogger;

class AlgoliaConnectionRetryTest extends ReviewTestCase
{
    public function test_application_registers_the_connection_compatible_transport()
    {
        $this->assertInstanceOf(AlgoliaHttpClient::class, Algolia::getHttpClient());
    }

    public function test_connection_failure_retries_the_same_search_on_the_next_host()
    {
        $history = [];
        $client = $this->client([
            new ConnectException('DNS resolution failed', new Request('POST', 'https://primary.example')),
            new Response(200, [], json_encode(['hits' => [['objectID' => 'App\\Piece::1']], 'nbHits' => 1])),
        ], $history);

        $result = $client->initIndex('local_pieces')->search('Arabesque');

        $this->assertSame(1, $result['nbHits']);
        $this->assertCount(2, $history);
        $this->assertSame('primary.example', $history[0]['request']->getUri()->getHost());
        $this->assertSame('fallback.example', $history[1]['request']->getUri()->getHost());
        foreach ($history as $attempt) {
            $this->assertSame('https', $attempt['request']->getUri()->getScheme());
            $this->assertSame('/1/indexes/local_pieces/query', $attempt['request']->getUri()->getPath());
            $this->assertSame(['query' => 'Arabesque'], json_decode((string) $attempt['request']->getBody(), true));
            $this->assertSame('test-secret', $attempt['request']->getHeaderLine('X-Algolia-API-Key'));
        }
        $this->assertSame(2, $history[0]['options']['connect_timeout']);
        $this->assertSame(4, $history[1]['options']['connect_timeout']);
    }

    public function test_connection_failure_retries_index_updates_without_changing_records()
    {
        $history = [];
        $client = $this->client([
            new ConnectException('Connection timed out', new Request('POST', 'https://primary.example')),
            new Response(200, [], json_encode(['taskID' => 123, 'objectIDs' => ['App\\Piece::1']])),
        ], $history);

        $client->initIndex('local_pieces')->saveObjects([['objectID' => 'App\\Piece::1', 'name' => 'Arabesque']]);

        $this->assertCount(2, $history);
        $this->assertSame('fallback.example', $history[1]['request']->getUri()->getHost());
        $firstBody = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame($firstBody, json_decode((string) $history[1]['request']->getBody(), true));
        $this->assertSame('Arabesque', $firstBody['requests'][0]['body']['name']);
    }

    public function test_an_invalid_api_key_is_reported_without_retrying()
    {
        $history = [];
        $client = $this->client([
            new Response(403, [], json_encode(['message' => 'Invalid Application-ID or API key', 'status' => 403])),
        ], $history);

        try {
            $client->initIndex('local_pieces')->search('Arabesque');
            $this->fail('Expected the invalid API key to be rejected.');
        } catch (\Algolia\AlgoliaSearch\Exceptions\AlgoliaException $exception) {
            $this->assertSame(403, $exception->getCode());
            $this->assertCount(1, $history);
        }
    }

    public function test_unrelated_transport_errors_are_not_retried()
    {
        $history = [];
        $client = $this->client([new \LogicException('Unexpected transport failure')], $history);
        $this->expectException(\LogicException::class);
        $client->initIndex('local_pieces')->search('Arabesque');
    }

    private function client(array $responses, array &$history): SearchClient
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));
        $transport = new AlgoliaHttpClient(new Guzzle6HttpClient(new Client(['handler' => $handler])));
        $config = SearchConfig::create('TESTAPP', 'test-secret');
        $hosts = ClusterHosts::create(['primary.example' => 10, 'fallback.example' => 0]);

        return new SearchClient(new ApiWrapper($transport, $config, $hosts, null, new NullLogger), $config);
    }
}
