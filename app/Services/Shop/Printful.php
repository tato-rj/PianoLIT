<?php

namespace App\Services\Shop;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Server-only, read-only foundation for the merchandise shop. */
class Printful
{
    public function stores(): array
    {
        return $this->get('stores')['result'];
    }

    public function products(int $offset = 0, ?int $limit = null): array
    {
        if ($offset < 0 || ($limit !== null && ($limit < 1 || $limit > 100))) {
            throw new PrintfulException('Invalid Printful catalog page.');
        }
        // Manual/API stores use Products API, rather than ecommerce-platform sync.
        return $this->get('store/products', $limit === null ? [] : compact('offset', 'limit'));
    }

    public function product(int $id): array
    {
        if ($id < 1) {
            throw new PrintfulException('A positive Printful product ID is required.');
        }

        return $this->get('store/products/'.$id)['result'];
    }

    public function storeId(): string
    {
        $id = (string) config('services.printful.store_id');
        if (!preg_match('/^[1-9][0-9]*$/D', $id)) {
            throw new PrintfulException('Configure a valid PRINTFUL_STORE_ID.');
        }

        return $id;
    }

    private function token(): string
    {
        $token = config('services.printful.token');
        $path = config('services.printful.token_file');
        if (!$token && is_string($path) && is_file($path) && is_readable($path) && filesize($path) <= 4096) {
            $token = @file_get_contents($path);
        }
        $token = is_string($token) ? trim($token) : '';
        if ($token === '' || strlen($token) > 4096 || preg_match('/\s/', $token)) {
            throw new PrintfulException('Configure PRINTFUL_API_TOKEN or a readable private token file.');
        }

        return $token;
    }

    private function get(string $path, array $query = []): array
    {
        $storeId = $this->storeId();
        $token = $this->token();

        try {
            $response = Http::acceptJson()->withToken($token)
                ->withHeaders(['X-PF-Store-Id' => $storeId])
                ->withOptions(['allow_redirects' => false, 'connect_timeout' => 5])
                ->timeout(15)->get('https://api.printful.com/'.$path, $query);
        } catch (ConnectionException $exception) {
            // Upstream exception text can contain request headers. Do not chain it.
            throw new PrintfulException('Printful could not be reached. Try again later.');
        }

        if (!$response->successful()) {
            if ($response->status() === 404 && preg_match('#^store/products/[1-9][0-9]*$#D', $path)) {
                throw new PrintfulProductNotFound('This product is no longer available in Printful.');
            }
            // Never display or log an upstream response body or request credentials.
            throw new PrintfulException('Printful request failed (HTTP '.$response->status().').');
        }

        $body = $response->json();
        if (!is_array($body) || ($body['code'] ?? null) !== 200 || !isset($body['result']) || !is_array($body['result'])) {
            throw new PrintfulException('Printful returned an unexpected response.');
        }

        return $body;
    }
}
