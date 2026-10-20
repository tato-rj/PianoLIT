<?php

namespace App\Services\Search;

use Algolia\AlgoliaSearch\Http\{Guzzle6HttpClient, HttpClientInterface};
use Algolia\AlgoliaSearch\Http\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;

class AlgoliaHttpClient implements HttpClientInterface
{
    private $client;

    public function __construct(HttpClientInterface $client = null)
    {
        $this->client = $client ?? new Guzzle6HttpClient;
    }

    public function sendRequest(RequestInterface $request, $timeout, $connectTimeout)
    {
        try {
            return $this->client->sendRequest($request, $timeout, $connectTimeout);
        } catch (ConnectException $exception) {
            // Guzzle 7 connection failures no longer extend RequestException.
            // A status-zero response lets Algolia's existing host retry policy run.
            return new Response(0, [], null, '1.1', $exception->getMessage());
        }
    }
}
