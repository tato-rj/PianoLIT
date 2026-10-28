<?php

namespace Tests\Review;

use App\Admin;
use App\Merchandise\{MerchandiseImage, MerchandiseProduct};
use App\Services\Admin\{MerchandiseCatalog, PrintfulCatalogSync};
use App\Services\Shop\PrintfulException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Http, Storage};

class PrintfulDeletionSyncTest extends ReviewTestCase
{
    private $remoteIds = [];
    private $details = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        config(['services.printful.store_id' => '12345', 'services.printful.token' => 'fake-deletion-token']);
        Storage::fake('public');
        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->fakeCatalog();
    }

    private function product(int $id = 7, int $store = 12345): MerchandiseProduct
    {
        $product = MerchandiseProduct::create(['printful_store_id' => $store, 'printful_product_id' => $id,
            'name' => 'Piano shirt', 'variants' => [], 'revision' => 4]);
        Storage::disk('public')->put('shop/merchandise/review-'.$id.'.png', 'private-review-image-bytes');
        $image = $product->images()->create(['disk' => 'public', 'path' => 'shop/merchandise/review-'.$id.'.png',
            'source' => 'upload', 'checksum' => hash('sha256', (string) $id), 'alt' => 'My own photograph',
            'position' => 1, 'width' => 64, 'height' => 64]);
        $product->update(['main_image_id' => $image->id]);
        return $product->fresh();
    }

    private function fakeCatalog(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('GET', $request->method());
            if ($request->url() === 'https://api.printful.com/stores') {
                return Http::response(['code' => 200, 'result' => [['id' => 12345]]]);
            }
            if (strpos($request->url(), 'https://api.printful.com/store/products?') === 0) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
                $offset = (int) $query['offset'];
                $rows = array_map(fn ($id) => ['id' => $id, 'name' => 'Remote '.$id], array_slice($this->remoteIds, $offset, (int) $query['limit']));
                return Http::response(['code' => 200, 'result' => $rows, 'paging' => ['offset' => $offset, 'total' => count($this->remoteIds)]]);
            }
            if (preg_match('#/store/products/([0-9]+)$#', $request->url(), $match)) {
                $id = (int) $match[1];
                $status = $this->details[$id] ?? 404;
                return Http::response(['code' => $status, 'result' => $status === 200
                    ? ['sync_product' => ['id' => $id, 'name' => 'Piano shirt'], 'sync_variants' => []]
                    : 'secret-upstream-error'], $status);
            }
            throw new \RuntimeException('Unexpected deletion-sync request');
        });
    }

    public function test_loading_directory_automatically_removes_confirmed_deleted_product_and_retains_images()
    {
        $product = $this->product();
        $image = $product->images()->first();
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('No merchandise yet')->assertDontSee('Piano shirt');
        $this->assertSame(0, MerchandiseProduct::count());
        $retired = MerchandiseProduct::withTrashed()->findOrFail($product->id);
        $this->assertTrue($retired->trashed());
        $this->assertSame($image->id, (int) $retired->main_image_id);
        $this->assertSame('My own photograph', MerchandiseImage::findOrFail($image->id)->alt);
        Storage::disk('public')->assertExists($image->path);
        $this->withExceptionHandling()->get(route('admin.merchandise.edit', $product))->assertNotFound();
    }

    public function test_refresh_of_deleted_product_returns_to_directory_with_a_clear_message()
    {
        $product = $this->product();
        $this->post(route('admin.merchandise.refresh', $product), ['revision' => $product->revision])
            ->assertRedirect(route('admin.merchandise.index'))
            ->assertSessionHas('status', 'This product was deleted in Printful and has been removed from your catalog.')
            ->assertSessionMissing('error');
        $this->assertTrue(MerchandiseProduct::withTrashed()->find($product->id)->trashed());
    }

    public function test_complete_pagination_keeps_products_on_later_pages_and_retires_only_missing_ids()
    {
        $this->remoteIds = range(1, 101);
        $retained = $this->product(101);
        $deleted = $this->product(777);
        $this->assertSame(1, app(PrintfulCatalogSync::class)->reconcile());
        $this->assertNotNull(MerchandiseProduct::find($retained->id));
        $this->assertNull(MerchandiseProduct::find($deleted->id));
        Http::assertSent(fn ($request) => strpos($request->url(), 'offset=100&limit=100') !== false);
        Http::assertNotSent(fn ($request) => substr($request->url(), -4) === '/101');
    }

    public function test_missing_list_entry_with_a_successful_detail_response_is_retained()
    {
        $product = $this->product();
        $this->details[7] = 200;
        $this->assertSame(0, app(PrintfulCatalogSync::class)->reconcile());
        $this->assertSame($product->getAttributes(), $product->fresh()->getAttributes());
    }

    /** @dataProvider temporaryFailures */
    public function test_detail_failures_other_than_404_never_remove_products($status)
    {
        $product = $this->product();
        $this->details[7] = $status;
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt')->assertSee('automatic checks will retry');
        $this->assertSame($product->getAttributes(), $product->fresh()->getAttributes());
    }

    public static function temporaryFailures(): array
    {
        return [[401], [403], [429], [500], [503]];
    }

    public function test_store_authentication_failure_preserves_the_catalog_even_if_products_would_be_404()
    {
        $product = $this->product();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['code' => 200, 'result' => [['id' => 99999]]])]);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt')->assertSee('automatic checks will retry');
        $this->assertNotNull(MerchandiseProduct::find($product->id));
        Http::assertSentCount(1);
    }

    public function test_network_failure_preserves_the_product_and_uses_safe_error_text()
    {
        $product = $this->product();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function () { throw new ConnectionException('secret-token'); });
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt')->assertDontSee('secret-token');
        $this->assertNotNull(MerchandiseProduct::find($product->id));
    }

    public function test_catalog_endpoint_404_is_not_treated_as_a_deleted_product()
    {
        $product = $this->product();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'api.printful.com/stores' => Http::response(['code' => 200, 'result' => [['id' => 12345]]]),
            '*' => Http::response(['code' => 404, 'result' => 'Not Found'], 404),
        ]);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt')->assertSee('automatic checks will retry');
        $this->assertNotNull(MerchandiseProduct::find($product->id));
    }

    public function test_catalog_total_changing_during_pagination_cancels_removal()
    {
        $product = $this->product();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'api.printful.com/stores' => Http::response(['code' => 200, 'result' => [['id' => 12345]]]),
            'api.printful.com/store/products*' => Http::sequence()
                ->push(['code' => 200, 'result' => [['id' => 8]], 'paging' => ['offset' => 0, 'total' => 2]])
                ->push(['code' => 200, 'result' => [['id' => 9]], 'paging' => ['offset' => 1, 'total' => 3]]),
        ]);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt');
        $this->assertNotNull(MerchandiseProduct::find($product->id));
        Http::assertSentCount(3);
    }

    /** @dataProvider incompleteCatalogs */
    public function test_incomplete_or_invalid_catalog_pages_cannot_remove_local_products($page)
    {
        $product = $this->product();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'api.printful.com/stores' => Http::response(['code' => 200, 'result' => [['id' => 12345]]]),
            '*' => Http::response(['code' => 200] + $page),
        ]);
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Piano shirt');
        $this->assertSame($product->getAttributes(), $product->fresh()->getAttributes());
        Http::assertSentCount(2);
    }

    public static function incompleteCatalogs(): array
    {
        return [
            [['result' => []]],
            [['result' => [], 'paging' => ['offset' => 100, 'total' => 0]]],
            [['result' => [], 'paging' => ['offset' => 0, 'total' => 1]]],
            [['result' => [['id' => 8], ['id' => 8]], 'paging' => ['offset' => 0, 'total' => 2]]],
            [['result' => [['id' => 8]], 'paging' => ['offset' => 0, 'total' => 0]]],
            [['result' => [['id' => '../7']], 'paging' => ['offset' => 0, 'total' => 1]]],
        ];
    }

    public function test_other_store_products_are_not_checked_or_removed()
    {
        $other = $this->product(99, 99999);
        $this->assertSame(0, app(PrintfulCatalogSync::class)->reconcile());
        $this->assertNotNull(MerchandiseProduct::find($other->id));
        Http::assertNothingSent();
    }

    public function test_explicit_reimport_of_a_returned_product_restores_its_curated_images()
    {
        $product = $this->product();
        app(PrintfulCatalogSync::class)->reconcile();
        $this->details[7] = 200;
        $restored = app(MerchandiseCatalog::class)->import(7)[0];
        $this->assertSame($product->id, $restored->id);
        $this->assertFalse($restored->trashed());
        $this->assertSame($product->main_image_id, $restored->main_image_id);
        $this->assertSame('My own photograph', $restored->images()->first()->alt);
        $this->assertSame(1, MerchandiseProduct::withTrashed()->count());
    }

    public function test_archival_failure_rolls_back_revision_and_keeps_product_and_files()
    {
        $product = $this->product();
        $before = $product->getAttributes();
        MerchandiseProduct::deleting(function () { throw new \RuntimeException('Simulated archival failure'); });
        try {
            app(PrintfulCatalogSync::class)->reconcile();
            $this->fail('Expected rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated archival failure', $exception->getMessage());
        } finally {
            MerchandiseProduct::flushEventListeners();
        }
        $this->assertSame($before, $product->fresh()->getAttributes());
        Storage::disk('public')->assertExists($product->images()->first()->path);
    }

    public function test_background_command_removes_deleted_products_without_an_admin_page_visit()
    {
        $product = $this->product();
        $this->artisan('printful:sync-catalog')
            ->expectsOutput('Printful catalog checked; 1 deleted product(s) removed from the local catalog.')->assertExitCode(0);
        $this->assertNull(MerchandiseProduct::find($product->id));
        $this->assertNotNull(MerchandiseProduct::withTrashed()->find($product->id));
    }
}
