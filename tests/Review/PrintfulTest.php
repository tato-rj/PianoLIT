<?php

namespace Tests\Review;

use App\Services\Shop\{Printful, PrintfulException};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PrintfulTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config([
            'services.printful.token' => 'fake-review-printful-token',
            'services.printful.token_file' => null,
            'services.printful.store_id' => '12345',
        ]);
        Http::swap(new \Illuminate\Http\Client\Factory);
    }

    public function test_reads_the_manual_store_and_variant_data_with_server_headers()
    {
        Http::fake([
            'api.printful.com/stores' => Http::response(['code' => 200, 'result' => [['id' => 12345, 'name' => 'Review store']]]),
            'api.printful.com/store/products/7' => Http::response(['code' => 200, 'result' => [
                'sync_product' => ['id' => 7], 'sync_variants' => [['id' => 9, 'size' => 'M', 'retail_price' => '20.00']],
            ]]),
            'api.printful.com/store/products' => Http::response(['code' => 200, 'result' => [['id' => 7]], 'paging' => ['total' => 1]]),
        ]);
        $client = app(Printful::class);
        $this->assertSame(12345, $client->stores()[0]['id']);
        $this->assertSame(1, $client->products()['paging']['total']);
        $this->assertSame('M', $client->product(7)['sync_variants'][0]['size']);
        Http::assertSentCount(3);
        foreach (Http::recorded() as [$request]) {
            $this->assertSame('GET', $request->method());
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer fake-review-printful-token'));
            $this->assertTrue($request->hasHeader('X-PF-Store-Id', '12345'));
            $this->assertTrue($request->hasHeader('Accept', 'application/json'));
        }
    }

    public function test_check_accepts_an_empty_catalog_and_performs_only_gets()
    {
        Http::fake([
            'api.printful.com/stores' => Http::response(['code' => 200, 'result' => [['id' => 12345]]]),
            'api.printful.com/store/products' => Http::response(['code' => 200, 'result' => [], 'paging' => ['total' => 0]]),
        ]);
        $this->artisan('printful:check')->expectsOutput('Connected to Printful store 12345')
            ->expectsOutput('Store products: 0')->expectsOutput('Read-only check complete')->assertExitCode(0);
        Http::assertSentCount(2);
        foreach (Http::recorded() as [$request]) $this->assertSame('GET', $request->method());
    }

    public function test_check_rejects_a_token_for_another_store_before_reading_products()
    {
        Http::fake(['*' => Http::response(['code' => 200, 'result' => [['id' => 98765]]])]);
        $this->artisan('printful:check')->expectsOutput('The token does not include the configured Printful store.')->assertExitCode(1);
        Http::assertSentCount(1);
    }

    public function test_private_file_token_is_loaded_without_an_environment_edit()
    {
        $path = tempnam(sys_get_temp_dir(), 'printful-review-');
        try {
            file_put_contents($path, "fake-file-token\n");
            config(['services.printful.token' => null, 'services.printful.token_file' => $path]);
            Http::fake(['*' => Http::response(['code' => 200, 'result' => []])]);
            $this->assertSame([], app(Printful::class)->products()['result']);
            Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer fake-file-token'));
        } finally {
            unlink($path);
        }
    }

    public function test_missing_token_fails_without_a_request()
    {
        config(['services.printful.token' => null]);
        $this->artisan('printful:check')->expectsOutput('Configure PRINTFUL_API_TOKEN or a readable private token file.')->assertExitCode(1);
        Http::assertNothingSent();
    }

    /** @dataProvider failedStatuses */
    public function test_http_failures_do_not_leak_raw_errors_or_follow_redirects($status)
    {
        Http::fake(function ($request, $options) use ($status) {
            $this->assertFalse($options['allow_redirects']);
            $this->assertSame(5, $options['connect_timeout']);
            $this->assertSame(15, $options['timeout']);
            return Http::response(['error' => 'fake-review-printful-token private-upstream-text'], $status,
                ['Location' => 'https://example.invalid/collect']);
        });
        try {
            app(Printful::class)->products();
            $this->fail('Expected a safe Printful exception');
        } catch (PrintfulException $exception) {
            $this->assertSame('Printful request failed (HTTP '.$status.').', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        Http::assertSentCount(1);
    }

    public static function failedStatuses(): array
    {
        return [[302], [401], [403], [429], [500]];
    }

    /** @dataProvider malformedResponses */
    public function test_invalid_success_envelopes_are_rejected($body)
    {
        Http::fake(['*' => Http::response($body)]);
        $this->expectException(PrintfulException::class);
        $this->expectExceptionMessage('Printful returned an unexpected response.');
        app(Printful::class)->products();
    }

    public static function malformedResponses(): array
    {
        return [['not-json'], [['result' => []]], [['code' => 400, 'result' => []]],
            [['code' => 200, 'result' => null]], [['code' => 200, 'result' => 'unexpected']]];
    }

    public function test_connection_errors_are_sanitized_without_chaining_the_request()
    {
        Http::fake(function () { throw new ConnectionException('fake-review-printful-token private-upstream-text'); });
        try {
            app(Printful::class)->products();
            $this->fail('Expected a safe Printful exception');
        } catch (PrintfulException $exception) {
            $this->assertSame('Printful could not be reached. Try again later.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_invalid_store_id_fails_before_network_access()
    {
        config(['services.printful.store_id' => '12345/../other-store']);
        $this->artisan('printful:check')->expectsOutput('Configure a valid PRINTFUL_STORE_ID.')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_nonpositive_product_id_fails_before_network_access()
    {
        $this->expectException(PrintfulException::class);
        app(Printful::class)->product(0);
    }
}
