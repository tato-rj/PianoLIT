<?php

namespace Tests\Review;

use App\Admin;
use App\Merchandise\MerchandiseProduct;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Route, Schema, Storage};

class ShopDisplayRetirementTest extends ReviewTestCase
{
    public function test_removal_and_schema_rollback_preserve_regular_product_artwork_and_records()
    {
        $products = $this->products();
        $images = DB::table('merchandise_images')->get()->toJson();
        require_once database_path('migrations/2026_11_05_100000_remove_shop_display_mockups.php');
        $migration = new \RemoveShopDisplayMockups;
        $originals = [];
        foreach ($products as $product) {
            $originals[$product->getTable()] = $product->refresh()->getRawOriginal();
        }
        $migration->down();
        foreach ($products as $product) {
            $this->assertNull(DB::table($product->getTable())->where('id', $product->id)->value('display_mockup_path'));
            DB::table($product->getTable())->where('id', $product->id)->update(['display_mockup_path' => 'shop/display-mockups/retired.png']);
        }

        $migration->up();
        foreach ($products as $product) {
            $this->assertFalse(Schema::hasColumn($product->getTable(), 'display_mockup_path'));
            $this->assertEquals($originals[$product->getTable()], $product->refresh()->getRawOriginal());
            $this->assertArrayNotHasKey('display_mockup_path', $product->toArray());
        }
        $this->assertSame($images, DB::table('merchandise_images')->get()->toJson());
    }

    public function test_product_editors_and_design_system_no_longer_offer_display_artwork_or_best_deals()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        foreach ($this->products() as $category => $product) {
            $this->assertFalse(Route::has('admin.'.$category.'.display-mockup'));
            $this->get(route('admin.'.$category.'.edit', $product))->assertOk()
                ->assertDontSee('Display mockup')->assertDontSee('name="display_mockup"', false);
        }
        $this->get(route('admin.design-system.index'))->assertOk()->assertDontSee('Shop best deals');
        Http::assertNothingSent();
    }

    private function products(): array
    {
        Storage::fake('public');
        config(['services.printful.store_id' => '12345']);
        $merchandise = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 77,
            'name' => 'Piano T-shirt', 'variants' => []]);
        $image = $merchandise->images()->create(['disk' => 'public', 'path' => 'shop/original-shirt.png', 'source' => 'upload',
            'alt' => 'Original shirt', 'checksum' => hash('sha256', 'original'), 'position' => 1, 'width' => 600, 'height' => 600]);
        $merchandise->update(['main_image_id' => $image->id]);
        $products = ['merchandise' => $merchandise];
        foreach (['ebooks' => eBook::class, 'escores' => eScore::class] as $category => $model) {
            $products[$category] = Model::withoutEvents(fn () => create($model, ['title' => 'Piano '.$category,
                'published_at' => now(), 'cover_path' => 'shop/original-cover.png', 'mockup_path' => 'shop/original-mockup.png']));
        }
        return $products;
    }
}
