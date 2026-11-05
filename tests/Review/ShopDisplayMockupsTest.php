<?php

namespace Tests\Review;

use App\Admin;
use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{DisplayMockups, Storefront};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Http, Storage};

class ShopDisplayMockupsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['services.printful.store_id' => '12345']);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function products(): array
    {
        $merchandise = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 77, 'revision' => 1,
            'name' => 'Piano T-shirt', 'variants' => [['id' => 91, 'variant_id' => 4011, 'name' => 'Piano T-shirt / M', 'sku' => 'PIANO-M',
                'size' => 'M', 'color' => 'Black', 'synced' => true, 'retail_price' => '25.00', 'currency' => 'USD', 'discontinued' => false, 'out_of_stock' => false]]]);
        $image = $merchandise->images()->create(['disk' => 'public', 'path' => 'shop/original-shirt.png', 'source' => 'upload', 'alt' => 'Original shirt',
            'checksum' => hash('sha256', 'original'), 'position' => 1, 'width' => 600, 'height' => 600]);
        $merchandise->update(['main_image_id' => $image->id]);
        $products = ['merchandise' => $merchandise];
        foreach (['ebooks' => eBook::class, 'escores' => eScore::class] as $category => $model) {
            $products[$category] = Model::withoutEvents(fn () => create($model, ['title' => 'Display '.$category, 'slug' => 'display-'.$category,
                'published_at' => now(), 'price' => 20, 'discount' => 0, 'cover_path' => 'shop/original-cover.png', 'mockup_path' => 'shop/original-mockup.png']));
        }
        return $products;
    }

    private function payload(Model $product, array $extra = []): array
    {
        return $extra + ['action' => 'upload', 'mockup_version' => DisplayMockups::version($product),
            'revision' => $product instanceof MerchandiseProduct ? $product->revision : null,
            'display_mockup' => UploadedFile::fake()->image('display.png', 800, 600)];
    }

    public function test_each_editor_saves_separate_artwork_and_best_deals_uses_it_without_changing_other_images_or_json()
    {
        foreach ($this->products() as $category => $product) {
            $keys = array_keys($product->fresh()->toArray());
            $original = $category === 'merchandise' ? $product->main_image_id : $product->mockup_path;
            $this->get(route('admin.'.$category.'.edit', $product))->assertOk()->assertSee('Display mockup')->assertSee('name="display_mockup"', false);
            $this->post(route('admin.'.$category.'.display-mockup', $product), $this->payload($product) + ['display_mockup_path' => 'forged/path.png'])
                ->assertRedirect(route('admin.'.$category.'.edit', $product).'#display-mockup');
            $product->refresh();
            $this->assertStringStartsWith('shop/display-mockups/', $product->display_mockup_path);
            Storage::disk('public')->assertExists($product->display_mockup_path);
            $this->assertSame('image/png', getimagesizefromstring(Storage::disk('public')->get($product->display_mockup_path))['mime']);
            $this->assertSame($keys, array_keys($product->toArray()));
            $this->assertArrayNotHasKey('display_mockup_path', $product->toArray());
            $this->assertSame($original, $category === 'merchandise' ? $product->main_image_id : $product->mockup_path);
            $card = $category === 'merchandise' ? app(Storefront::class)->present($product) : app(Storefront::class)->presentDigital($product);
            $this->assertSame($product->displayMockupUrl(), $card['displayMockupUrl']);
            $this->assertNotSame($card['imageUrl'], $card['displayMockupUrl']);
            $this->get(route('admin.'.$category.'.edit', $product))->assertOk()->assertSee($product->displayMockupUrl(), false)->assertSee('Remove display mockup');
        }
        $response = $this->get(route('shop.home'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(3, $xpath->query('//section[@aria-labelledby="shop-best-deals-title"]//img[contains(@src, "/shop/display-mockups/")]')->length);
        $this->assertSame(0, $xpath->query('//article[contains(@class, "shop-card")]//img[contains(@src, "/shop/display-mockups/")]')->length);
        Http::assertNothingSent();
    }

    public function test_replace_remove_and_stale_writes_preserve_current_images_and_clean_unused_uploads()
    {
        foreach ($this->products() as $category => $product) {
            $url = route('admin.'.$category.'.display-mockup', $product);
            $originalPayload = $this->payload($product);
            $this->post($url, $originalPayload)->assertRedirect();
            $product->refresh();
            $first = $product->display_mockup_path;
            $this->withExceptionHandling()->post($url, $this->payload($product, ['mockup_version' => $originalPayload['mockup_version']]))
                ->assertSessionHasErrors('display_mockup', null, 'displayMockup');
            $this->assertSame($first, $product->fresh()->display_mockup_path);
            $this->assertSame([$first], Storage::disk('public')->allFiles('shop/display-mockups'));
            $this->post($url, $this->payload($product))->assertRedirect();
            $product->refresh();
            Storage::disk('public')->assertMissing($first);
            Storage::disk('public')->assertExists($product->display_mockup_path);
            $this->post($url, ['action' => 'remove', 'mockup_version' => DisplayMockups::version($product), 'revision' => $product->revision])->assertRedirect();
            $this->assertNull($product->fresh()->display_mockup_path);
            $this->assertSame([], Storage::disk('public')->allFiles('shop/display-mockups'));
        }
    }

    public function test_uploads_reject_urls_svg_oversized_images_and_stale_merchandise_revisions()
    {
        $product = $this->products()['merchandise'];
        $url = route('admin.merchandise.display-mockup', $product);
        $this->withExceptionHandling();
        foreach (['https://example.com/image.png', UploadedFile::fake()->create('image.svg', 1, 'image/svg+xml'),
            UploadedFile::fake()->image('wide.png', 6001, 1), UploadedFile::fake()->image('large.png')->size(10241)] as $file) {
            $this->post($url, $this->payload($product, ['display_mockup' => $file]))->assertSessionHasErrors('display_mockup', null, 'displayMockup');
        }
        $this->post($url, $this->payload($product, ['revision' => 999]))->assertSessionHasErrors('display_mockup', null, 'displayMockup');
        $this->assertNull($product->fresh()->display_mockup_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertNothingSent();
    }

    public function test_mockup_routes_retain_admin_authentication_and_merchandise_manager_permissions()
    {
        $products = $this->products();
        auth('admin')->logout();
        $this->withExceptionHandling();
        foreach ($products as $category => $product) {
            $this->post(route('admin.'.$category.'.display-mockup', $product), $this->payload($product))->assertRedirect(route('admin.login.show'));
        }
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->post(route('admin.merchandise.display-mockup', $products['merchandise']), $this->payload($products['merchandise']))->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_retirement_preserves_mockups_and_permanent_deletion_cleans_only_owned_mockup_paths()
    {
        $products = $this->products();
        $product = $products['merchandise'];
        $this->post(route('admin.merchandise.display-mockup', $product), $this->payload($product))->assertRedirect();
        $product->refresh();
        $product->delete();
        Storage::disk('public')->assertExists($product->display_mockup_path);
        $product->restore();
        $product->forceDelete();
        Storage::disk('public')->assertMissing($product->display_mockup_path);
        Storage::disk('public')->put('unrelated.png', 'keep');
        app(DisplayMockups::class)->discard('unrelated.png');
        Storage::disk('public')->assertExists('unrelated.png');
        $this->assertNull(DisplayMockups::url('https://example.com/image.png'));
        foreach (['ebooks', 'escores'] as $category) {
            $digital = $products[$category];
            $this->post(route('admin.'.$category.'.display-mockup', $digital), $this->payload($digital))->assertRedirect();
            $digital->refresh();
            $path = $digital->display_mockup_path;
            DB::beginTransaction();
            $digital->delete();
            Storage::disk('public')->assertExists($path);
            DB::rollBack();
            Storage::disk('public')->assertExists($path);
            $digital->newQuery()->findOrFail($digital->getKey())->delete();
            Storage::disk('public')->assertMissing($path);
        }
    }
}
