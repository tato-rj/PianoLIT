<?php

namespace Tests\Review;

use App\{Admin};
use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{ProductNames, Storefront};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http};
use Illuminate\Validation\ValidationException;

class ShopCategoryUrlsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345']);
    }

    private function product(array $attributes = [])
    {
        return MerchandiseProduct::create($attributes + ['printful_store_id' => 12345, 'printful_product_id' => random_int(1, 1000000),
            'name' => 'Piano rainbow T-shirt · Mint', 'variants' => []]);
    }

    public function test_categories_are_real_shop_pages_with_canonical_breadcrumbs_and_links()
    {
        $product = $this->product();
        foreach (['t-shirts', 'stickers', 'ebooks', 'escores'] as $category) {
            $url = app(Storefront::class)->categoryUrl($category);
            $this->assertSame('http://shop.localhost/'.$category, $url);
            $response = $this->get($url)->assertOk()->assertViewIs('shop.storefront.index');
            $this->assertSame($category, $response->viewData('category'));
            $this->assertSame(Storefront::CATEGORIES[$category], collect($response->viewData('breadcrumbs'))->last()['label']);
            $response->assertSee('action="'.$url.'"', false)->assertSee('href="http://shop.localhost/t-shirts"', false)
                ->assertDontSee('name="category"', false)->assertDontSee('shop-category-title');
        }
        $this->get(app(Storefront::class)->categoryUrl('t-shirts'))->assertSee('href="'.app(Storefront::class)->productUrl($product).'"', false);
        Http::assertNothingSent();
    }

    public function test_merchandise_uses_persisted_slug_and_correct_category_on_every_public_link()
    {
        $product = $this->product();
        $url = app(Storefront::class)->productUrl($product);
        $this->assertSame('http://shop.localhost/t-shirts/piano-rainbow-t-shirt-mint', $url);
        $this->get($url)->assertOk()->assertViewIs('shop.storefront.show')->assertSee('href="http://shop.localhost/t-shirts"', false);
        $this->get(route('shop.home'))->assertOk()->assertSee('href="'.$url.'"', false)->assertDontSee('/products/'.$product->id);
        $product->update(['name' => 'A revised title', 'variants' => [['product_name' => 'T-shirt']]]);
        $this->assertSame($url, app(Storefront::class)->productUrl($product));
        $this->get($url)->assertOk()->assertSee('A revised title');
    }

    public function test_legacy_ids_filters_and_wrong_categories_redirect_with_raw_queries()
    {
        $product = $this->product();
        $url = app(Storefront::class)->productUrl($product);
        foreach (['GET', 'HEAD'] as $method) {
            $this->call($method, route('shop.merchandise.legacy', $product).'?utm_source=A%20B&x=1&x=2')->assertStatus(301)->assertRedirect($url.'?utm_source=A%20B&x=1&x=2');
            $this->call($method, 'http://shop.localhost/stickers/'.$product->slug.'?x=%2F')->assertStatus(301)->assertRedirect($url.'?x=%2F');
            $this->call($method, 'http://shop.localhost/?category=t-shirts&q=Mint%20piano&page=2')->assertStatus(301)
                ->assertRedirect('http://shop.localhost/t-shirts?q=Mint%20piano&page=2');
            $this->call($method, 'http://shop.localhost/?category=escores')->assertStatus(301)->assertRedirect('http://shop.localhost/escores');
        }
        $this->withExceptionHandling()->get('http://shop.localhost/t-shirts/missing')->assertNotFound();
        $this->get('http://shop.localhost/not-a-category')->assertNotFound();
    }

    public function test_slugs_do_not_change_admin_bindings_or_leak_other_stores_and_retired_products()
    {
        $product = $this->product();
        $this->assertStringContainsString('/merchandise/'.$product->id.'/edit', route('admin.merchandise.edit', $product));
        $other = $this->product(['printful_store_id' => 999, 'name' => 'Foreign T-shirt']);
        $this->withExceptionHandling()->get(app(Storefront::class)->productUrl($other))->assertNotFound();
        $product->delete();
        $this->get(app(Storefront::class)->productUrl($product))->assertNotFound();
        $this->get(route('shop.merchandise.legacy', $product))->assertNotFound();
    }

    public function test_category_search_and_topics_stay_on_the_category_path_without_limiting_the_grid()
    {
        for ($i = 0; $i < 13; $i++) $this->product(['name' => 'Mint T-shirt '.$i]);
        $response = $this->get('http://shop.localhost/t-shirts?q=Mint&page=2')->assertOk();
        $this->assertCount(13, $response->viewData('products'));
        $response->assertDontSee('shop-pagination')->assertDontSee('data-category-track');
        $this->assertSame('Mint', $response->viewData('search'));
        $this->withExceptionHandling()->getJson('http://shop.localhost/ebooks?topics=invalid')->assertStatus(422);
    }

    public function test_new_names_reject_duplicate_names_and_colliding_slugs_across_product_types()
    {
        $product = $this->product();
        $names = app(ProductNames::class);
        $this->assertSame($product->name, $names->validate($product->name, $product));
        foreach (['PIANO RAINBOW T-SHIRT · MINT', 'Piano rainbow t shirt mint'] as $duplicate) {
            try { $names->validate($duplicate); $this->fail('Duplicate name or URL was accepted.'); }
            catch (ValidationException $exception) { $this->assertArrayHasKey('title', $exception->errors()); }
        }
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin')->withExceptionHandling();
        foreach (['ebooks' => eBook::class, 'escores' => eScore::class] as $category => $model) {
            $digital = Model::withoutEvents(fn () => create($model, ['title' => 'Existing '.$category, 'slug' => 'existing-'.$category]));
            $this->postJson(route('admin.'.$category.'.store'), ['title' => $product->name])->assertStatus(422)->assertJsonValidationErrors('title');
            $this->patchJson(route('admin.'.$category.'.update', $digital), ['title' => $product->name])->assertStatus(422)->assertJsonValidationErrors('title');
            $this->assertSame('Existing '.$category, $digital->fresh()->title);
        }
        $this->postJson(route('admin.ebooks.store'), ['title' => '!!!'])->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_backfill_preserves_records_and_gives_legacy_collisions_distinct_stable_slugs()
    {
        $migration = new \AddMerchandiseProductSlugs;
        $migration->down();
        $ids = [];
        foreach (['Piano shirt', 'Piano-shirt', '!!!'] as $i => $name) {
            $ids[] = DB::table('merchandise_products')->insertGetId(['printful_store_id' => 12345, 'printful_product_id' => 100 + $i,
                'name' => $name, 'variants' => '[]', 'deleted_at' => $i === 1 ? now() : null]);
        }
        $migration->up();
        $this->assertSame(['piano-shirt', 'piano-shirt-2', 'product'], DB::table('merchandise_products')->whereIn('id', $ids)->orderBy('id')->pluck('slug')->all());
        $this->assertSame(1, DB::table('merchandise_products')->whereNotNull('deleted_at')->count());
        try {
            DB::table('merchandise_products')->insert(['printful_store_id' => 12345, 'printful_product_id' => 999, 'name' => 'Other', 'slug' => 'piano-shirt', 'variants' => '[]']);
            $this->fail('Duplicate URL was accepted.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('UNIQUE', $exception->getMessage());
        }
    }
}
