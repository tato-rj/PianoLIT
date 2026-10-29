<?php

namespace Tests\Review;

use App\{Admin, Merchandise\MerchandiseProduct};
use App\Services\Admin\PrintfulCatalogSync;
use App\Services\Shop\{Printful, PrintfulException, Storefront};
use Illuminate\Support\Facades\{Http, Storage};

class LocalMerchandiseTest extends ReviewTestCase
{
    protected function tearDown(): void
    {
        // Restore the test environment before the isolated migration rollback.
        $this->app['env'] = 'testing';
        parent::tearDown();
    }

    public function test_local_runtime_blocks_all_printful_calls_even_with_enabled_cached_credentials()
    {
        $this->app['env'] = 'local';
        config(['services.printful.enabled' => true, 'services.printful.token' => 'fake-old-cached-token']);
        $api = app(Printful::class);
        $this->assertFalse($api->enabled());
        foreach (['stores' => [], 'products' => [], 'product' => [123], 'catalogProduct' => [71], 'catalogVariant' => [4011]] as $method => $arguments) {
            try {
                $api->$method(...$arguments);
                $this->fail('Local requests must stop before HTTP.');
            } catch (PrintfulException $exception) {
                $this->assertSame('Printful is disconnected in this environment.', $exception->getMessage());
            }
        }
        $this->assertSame(0, app(PrintfulCatalogSync::class)->reconcile());
        $this->artisan('printful:check')->expectsOutput('Printful is disconnected in this environment. No API request was made.')->assertExitCode(0);
        $this->artisan('printful:sync-catalog')->expectsOutput('Printful is disconnected in this environment. Scheduled checks are skipped.')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_samples_are_local_only_and_repeat_seeding_preserves_curation()
    {
        Storage::fake('public');
        $this->app['env'] = 'local';
        $this->artisan('shop:seed-demo')->assertExitCode(0);
        $this->assertSame(6, app(Storefront::class)->query()->count());
        $product = MerchandiseProduct::firstOrFail();
        $product->update(['name' => 'My edited local sample', 'main_image_id' => $product->images()->orderByDesc('id')->first()->id]);
        $main = $product->main_image_id;
        $this->artisan('shop:seed-demo')->assertExitCode(0);
        $this->assertDatabaseCount('merchandise_products', 6);
        $this->assertSame('My edited local sample', $product->fresh()->name);
        $this->assertSame($main, $product->fresh()->main_image_id);
        $variants = $product->variants;
        foreach ($variants as &$variant) unset($variant['color_code'], $variant['color_code2']);
        unset($variant);
        $product->update(['variants' => $variants]);
        $this->artisan('shop:seed-demo', ['--refresh-colors' => true])->assertExitCode(0);
        $this->assertNotNull($product->fresh()->variants[0]['color_code']);
        $this->assertSame('My edited local sample', $product->fresh()->name);
        $this->assertSame($main, $product->fresh()->main_image_id);
        $revision = $product->fresh()->revision;
        $this->artisan('shop:seed-demo', ['--refresh-colors' => true])->assertExitCode(0);
        $this->assertSame($revision, $product->fresh()->revision);
        foreach ($product->images as $image) Storage::disk('public')->assertExists($image->path);
        $this->get(route('shop.home'))->assertOk()->assertSee('More piano sticker')->assertSee('My edited local sample');
        $this->get(app(\App\Services\Shop\Storefront::class)->productUrl($product))->assertOk()->assertSee('Add to bag');
        $this->postJson(route('shop.bag.add'), ['_token' => session()->token(), 'product_id' => $product->id, 'variant_id' => $product->variants[0]['id'], 'quantity' => 1])->assertOk()->assertJson(['count' => 1]);
        $this->get(route('shop.bag'))->assertOk()->assertSee('My edited local sample');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->get(route('admin.merchandise.index'))->assertOk()->assertSee('Printful is disconnected on this machine')->assertDontSee('Import from Printful');
        $this->get(route('admin.merchandise.edit', $product))->assertOk()->assertDontSee('Refresh from Printful')->assertSee('Upload images');
        Http::assertNothingSent();
    }

    public function test_production_rejects_seeding_and_excludes_demo_rows_even_if_the_database_is_copied()
    {
        $demo = MerchandiseProduct::create(['printful_store_id' => 0, 'printful_product_id' => 1001, 'name' => 'Never public sample', 'variants' => []]);
        $this->app['env'] = 'production';
        $this->assertTrue(app(Printful::class)->enabled());
        $this->artisan('shop:seed-demo')->expectsOutput('Sample merchandise can only be created in the local environment.')->assertExitCode(1);
        $this->assertDatabaseCount('merchandise_products', 1);
        $this->assertSame(0, app(Storefront::class)->query()->count());
        $this->withExceptionHandling()->get(app(\App\Services\Shop\Storefront::class)->productUrl($demo))->assertNotFound();
        Http::assertNothingSent();
    }
}
