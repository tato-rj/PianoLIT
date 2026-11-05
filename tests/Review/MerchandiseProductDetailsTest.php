<?php

namespace Tests\Review;

use App\{Admin, Merchandise\MerchandiseProduct};
use App\Services\Shop\Storefront;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class MerchandiseProductDetailsTest extends ReviewTestCase
{
    private $product;

    public function setUp(): void
    {
        parent::setUp();
        config(['services.printful.store_id' => '12345']);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->product = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 77,
            'name' => 'Piano T-shirt', 'printful_name' => 'Piano T-shirt', 'variants' => [
                ['id' => 91, 'variant_id' => 4011, 'name' => 'Piano shirt / Ivory / M', 'sku' => null, 'color' => 'Ivory', 'size' => 'M', 'synced' => true,
                    'discontinued' => false, 'out_of_stock' => false, 'retail_price' => '25.00', 'currency' => 'USD'],
            ], 'revision' => 1]);
    }

    private function update(array $changes = [], array $query = [])
    {
        return $this->patchJson(route('admin.merchandise.update', ['merchandise' => $this->product] + $query),
            $changes + ['revision' => $this->product->fresh()->revision, 'name' => $this->product->fresh()->name,
                'subtitle' => 'A piano keyboard design', 'description' => "A soft cotton tee.\nPrinted in blue."]);
    }

    public function test_managers_can_save_product_copy_without_changing_urls_variants_or_gallery()
    {
        $catalog = app(Storefront::class);
        $url = $catalog->productUrl($this->product);
        $variants = $this->product->variants;
        $this->update(['name' => 'The wavy piano', 'slug' => 'untrusted', 'printful_store_id' => 9,
            'main_image_id' => 999, 'variants' => []], ['search' => 'Piano', 'sort' => 'name_asc'])
            ->assertRedirect(route('admin.merchandise.edit', ['merchandise' => $this->product, 'search' => 'Piano', 'sort' => 'name_asc']).'#product-details')
            ->assertSessionHas('status');
        $product = $this->product->fresh();
        $this->assertSame('The wavy piano', $product->name);
        $this->assertSame('The wavy piano', $product->name_override);
        $this->assertSame('Piano T-shirt', $product->printful_name);
        $this->assertSame('A piano keyboard design', $product->subtitle);
        $this->assertSame("A soft cotton tee.\nPrinted in blue.", $product->description);
        $this->assertSame($variants, $product->variants);
        $this->assertSame(12345, $product->printful_store_id);
        $this->assertNull($product->main_image_id);
        $this->assertSame(2, $product->revision);
        $this->assertSame($url, $catalog->productUrl($product), 'A local title does not change the category or permalink');
        foreach (['printful_name', 'name_override', 'subtitle', 'description'] as $field) $this->assertArrayNotHasKey($field, $product->toArray());
        $admin = $this->get(route('admin.merchandise.edit', $product))->assertOk()->assertSee('Product details')
            ->assertSee('name="subtitle"', false)->assertSee('name="description"', false)->assertDontSee('Manage product images');
        $shop = $this->get($url)->assertOk()->assertSee('The wavy piano')->assertSee('A piano keyboard design')
            ->assertSee('A soft cotton tee.')->assertSee('Printed in blue.')->assertSee('Fitting')->assertSee('Quality Guarantee');
        $this->assertSame('A piano keyboard design', $shop->viewData('product')['subtitle']);
        Http::assertNothingSent();
        if ($directory = getenv('PIANOLIT_PRODUCT_DETAILS_FIXTURES')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            foreach (['admin' => $admin, 'shop' => $shop] as $name => $response) {
                $html = str_replace(['http://localhost/', 'http://admin.localhost/', 'http://shop.localhost/', 'href="/css/', 'src="/js/'],
                    ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $response->getContent());
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
    }

    public function test_optional_copy_can_be_cleared_and_markup_remains_plain_text()
    {
        $this->update(['subtitle' => '<script>subtitle()</script>', 'description' => "<img src=x onerror=alert(1)>\nSecond line"])->assertRedirect();
        $this->get(app(Storefront::class)->productUrl($this->product))->assertOk()
            ->assertSee('&lt;script&gt;subtitle()&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x', false)
            ->assertDontSee('<script>subtitle()', false);
        $this->update(['subtitle' => '', 'description' => ''])->assertRedirect();
        $this->assertNull($this->product->fresh()->subtitle);
        $this->assertNull($this->product->fresh()->description);
        $this->assertNull($this->product->fresh()->name_override, 'Saving other fields does not pin the provider title');
    }

    private function importedMaterials(string $text): void
    {
        DB::table('merchandise_materials')->updateOrInsert(['product_id' => $this->product->id, 'sync_variant_id' => 91], [
            'catalog_variant_id' => 4011, 'catalog_product_id' => 71, 'color' => 'Ivory', 'size' => 'M',
            'composition' => '[]', 'description_text' => $text, 'updated_at' => now(),
        ]);
    }

    public function test_material_edits_are_separate_escaped_and_can_return_to_current_imported_text()
    {
        $this->importedMaterials('100% ring-spun cotton');
        $this->get(route('admin.merchandise.edit', $this->product))->assertOk()
            ->assertSee('name="materials"', false)->assertSee('100% ring-spun cotton')->assertDontSee('Use Printful materials');
        $custom = "Verified cotton fabric\n<img src=x onerror=alert(1)>";
        $this->update(['materials' => $custom])->assertRedirect();
        $product = $this->product->fresh();
        $this->assertSame($custom, $product->materials_override);
        $this->assertSame("A soft cotton tee.\nPrinted in blue.", $product->description);
        $this->assertArrayNotHasKey('materials_override', $product->toArray());
        $this->importedMaterials('Updated supplier material');
        $this->get(app(Storefront::class)->productUrl($product))->assertOk()
            ->assertSee('Verified cotton fabric')->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<img src=x', false)->assertDontSee('Updated supplier material');
        $this->get(route('admin.merchandise.edit', $product))->assertOk()
            ->assertSee('Use Printful materials')->assertSee('Updated supplier material')->assertSee('Verified cotton fabric');
        $resetUrl = route('admin.merchandise.materials.reset', ['merchandise' => $product, 'search' => 'Piano']);
        $this->patch($resetUrl, ['revision' => $product->revision])->assertRedirect(route('admin.merchandise.edit', ['merchandise' => $product, 'search' => 'Piano']).'#product-details');
        $this->assertNull($product->fresh()->materials_override);
        $this->assertSame("A soft cotton tee.\nPrinted in blue.", $product->fresh()->description);
        $this->get(app(Storefront::class)->productUrl($product))->assertOk()->assertSee('Updated supplier material')->assertDontSee('Verified cotton fabric');
        Http::assertNothingSent();
    }

    public function test_untouched_materials_keep_following_printful_and_deliberately_empty_edits_are_respected()
    {
        $this->importedMaterials("100% cotton\nMaterial content may vary by color");
        $this->update(['materials' => "100% cotton\r\nMaterial content may vary by color"])->assertRedirect();
        $this->assertNull($this->product->fresh()->materials_override);
        $this->importedMaterials('100% polyester');
        $this->get(app(Storefront::class)->productUrl($this->product))->assertOk()->assertSee('100% polyester');
        $this->update(['materials' => ''])->assertRedirect();
        $this->assertSame('', $this->product->fresh()->materials_override);
        $response = $this->get(app(Storefront::class)->productUrl($this->product))->assertOk()->assertDontSee('100% polyester');
        $this->assertSame([], $response->viewData('product')['materials']);
    }

    public function test_material_writes_and_reset_reject_invalid_stale_or_unowned_edits()
    {
        $this->withExceptionHandling();
        foreach ([['materials' => ['bad']], ['materials' => str_repeat('x', 6001)]] as $invalid) $this->update($invalid)->assertUnprocessable();
        $this->update(['materials' => 'Custom material'])->assertRedirect();
        $reset = route('admin.merchandise.materials.reset', $this->product);
        $this->patchJson($reset, ['revision' => 1])->assertUnprocessable();
        $this->assertSame('Custom material', $this->product->fresh()->materials_override);
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->patchJson($reset, ['revision' => 2])->assertForbidden();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->product->update(['printful_store_id' => 55]);
        $this->patchJson($reset, ['revision' => 2])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_a_materials_only_submission_keeps_existing_description_and_subtitle()
    {
        $this->update()->assertRedirect();
        $before = $this->product->fresh();
        $this->patchJson(route('admin.merchandise.update', $before), ['revision' => $before->revision,
            'name' => $before->name, 'materials' => 'My material correction'])->assertRedirect();
        $this->assertSame($before->description, $before->fresh()->description);
        $this->assertSame($before->subtitle, $before->fresh()->subtitle);
        $this->assertSame('My material correction', $before->fresh()->materials_override);
    }

    public function test_invalid_copy_reserved_names_and_stale_revisions_cannot_overwrite_details()
    {
        $this->withExceptionHandling();
        foreach ([['name' => ''], ['name' => ['bad']], ['subtitle' => ['bad']], ['subtitle' => str_repeat('x', 256)],
            ['description' => str_repeat('x', 10001)], ['revision' => 0], ['name' => '!!!']] as $bad) {
            $this->update($bad)->assertUnprocessable();
        }
        $other = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 88, 'name' => 'Reserved name', 'variants' => []]);
        $other->delete();
        $this->update(['name' => 'Reserved name'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame(1, $this->product->fresh()->revision);
        $this->assertNull($this->product->fresh()->description);
        $this->update(['name' => 'Local title'])->assertRedirect();
        $this->update(['revision' => 1, 'name' => 'Stale title'])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->patch(route('admin.merchandise.update', $this->product), ['revision' => 1, 'name' => 'Stale title'])
            ->assertRedirect()->assertSessionHasErrors('revision', null, 'product_details');
        $this->assertSame('Local title', $this->product->fresh()->name);
    }

    public function test_metadata_writes_require_the_manager_and_current_active_store_product()
    {
        $this->withExceptionHandling();
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->update()->assertForbidden();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->product->update(['printful_store_id' => 55]);
        $this->update()->assertNotFound();
        $this->product->update(['printful_store_id' => 12345]);
        $this->product->delete();
        $this->patchJson(route('admin.merchandise.update', $this->product), ['revision' => 1, 'name' => 'Retired'])->assertNotFound();
        $this->assertSame('Piano T-shirt', $this->product->fresh()->name);
        Http::assertNothingSent();
    }
}
