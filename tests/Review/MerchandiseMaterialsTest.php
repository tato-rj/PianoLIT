<?php

namespace Tests\Review;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{MerchandiseMaterials, Storefront};
use Illuminate\Support\Facades\DB;

class MerchandiseMaterialsTest extends ReviewTestCase
{
    public function test_incomplete_invalid_or_duplicate_material_percentages_are_not_published()
    {
        foreach ([null, 'cotton', [['name' => 'cotton', 'percentage' => 50]],
            [['name' => '<script>cotton</script>', 'percentage' => 100]],
            [['name' => 'cotton', 'percentage' => -1]], [['name' => 'cotton', 'percentage' => 101]],
            [['name' => 'cotton', 'percentage' => '100']], [['name' => 'cotton', 'percentage' => NAN]],
            [['name' => 'cotton', 'percentage' => 50], ['name' => 'Cotton', 'percentage' => 50]],
        ] as $value) $this->assertNull(MerchandiseMaterials::composition($value));
        $this->assertSame([], MerchandiseMaterials::composition([]));
    }

    public function test_size_specific_materials_are_labeled_and_missing_variants_never_inherit_a_blend()
    {
        $base = ['variant_id' => 4011, 'catalog_product_id' => 71, 'synced' => true, 'color' => 'Black', 'size' => 'M',
            'retail_price' => '25', 'currency' => 'USD'];
        $variants = [array_replace($base, ['id' => 91]), array_replace($base, ['id' => 92, 'variant_id' => 4012, 'size' => 'L'])];
        $product = MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => 77, 'name' => 'Piano T-shirt', 'variants' => $variants]);
        DB::table('merchandise_materials')->insert(['product_id' => $product->id, 'sync_variant_id' => 91, 'catalog_variant_id' => 4011,
            'catalog_product_id' => 71, 'color' => 'Black', 'size' => 'M', 'composition' => json_encode([['name' => 'cotton', 'percentage' => 100]]), 'updated_at' => now()]);
        $materials = app(MerchandiseMaterials::class);
        $presented = app(Storefront::class)->variants($product);
        $this->assertSame([
            ['label' => 'Black / M', 'text' => '100% cotton'],
            ['label' => 'Black / L', 'text' => 'Material details are not available for this option.'],
        ], $materials->forProduct($product, $presented));
        $variants[0]['color'] = 'Blue';
        $product->update(['variants' => $variants]);
        $this->assertSame([], $materials->forProduct($product, app(Storefront::class)->variants($product)));
    }
}
