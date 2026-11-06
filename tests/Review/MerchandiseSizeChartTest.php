<?php

namespace Tests\Review;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{SizeCharts, Storefront};
use Illuminate\Support\Facades\Http;

class MerchandiseSizeChartTest extends ReviewTestCase
{
    private function product(array $catalogIds): MerchandiseProduct
    {
        config(['services.printful.store_id' => '12345']);
        $variants = [];
        foreach ($catalogIds as $index => $catalogId) {
            $variants[] = ['id' => 91 + $index, 'variant_id' => 15000 + $index, 'catalog_product_id' => $catalogId,
                'synced' => true, 'size' => 'M', 'color' => 'Black', 'retail_price' => '28.50', 'currency' => 'USD',
                'out_of_stock' => false, 'discontinued' => false];
        }
        return MerchandiseProduct::create(['printful_store_id' => 12345, 'printful_product_id' => random_int(1000, 999999),
            'name' => 'Piano T-shirt', 'variants' => $variants]);
    }

    public function test_supplier_chart_uses_flat_width_center_back_sleeves_and_published_units()
    {
        $product = $this->product([586])->fresh();
        $before = $product->toArray();
        $response = $this->get(app(Storefront::class)->productUrl($product))->assertOk()
            ->assertSee('relaxed unisex fit')->assertSee('center back of the collar')
            ->assertSee('flat garment width')->assertSee('Product measurements may vary by up to 2″ (5 cm).')
            ->assertDontSee('Men can go one size up')->assertDontSee('Measure all the way around')
            ->assertSee('data-bs-target="#product-size-chart"', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//*[@id="product-size-chart"]//tbody/tr');
        $this->assertSame(['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'], array_map(fn ($row) => trim($row->firstElementChild->textContent), iterator_to_array($rows)));
        $this->assertSame(['Size', 'Length', 'Width', 'Sleeve length'], array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('//*[@id="product-size-chart"]//thead//th'))));
        // Eighth-inch fractions must survive; centimeters use supplier rounding.
        foreach ([0 => ['26 5/8″', '18 1/4″', '16 1/4″'], 2 => ['29 3/8″', '22″', '19″'], 6 => ['33 1/2″', '29 3/4″', '24 5/8″']] as $index => $expected) {
            $this->assertSame($expected, array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('.//td/span[contains(@class,"__in")]', $rows->item($index)))));
        }
        $this->assertSame(['71', '51.4', '45'], array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($xpath->query('.//td/span[contains(@class,"__cm")]', $rows->item(1)))));
        $this->assertSame($before, $product->fresh()->toArray());
        Http::assertNothingSent();

        // Disposable full-page preview for browser QA; never save fixture rows.
        if ($directory = getenv('PIANOLIT_SHOP_BROWSER_FIXTURES')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            $html = str_replace(['http://localhost/', 'http://shop.localhost/', 'href="/css/', 'src="/js/'],
                ['http://shop.pianolit.test/', 'http://shop.pianolit.test/', 'href="http://shop.pianolit.test/css/', 'src="http://shop.pianolit.test/js/'], $response->getContent());
            file_put_contents($directory.'/size-chart.html', $html);
        }
    }

    public function test_other_missing_and_mixed_catalog_identities_do_not_get_this_garment_guide()
    {
        foreach ([[71], [null], [586, 71], [586, null], []] as $catalogIds) {
            $product = $this->product($catalogIds);
            $this->assertNull(SizeCharts::forProduct($product));
            $this->get(app(Storefront::class)->productUrl($product))->assertOk()
                ->assertSee('Size and fit information is not available for this product.')
                ->assertDontSee('data-bs-target="#product-size-chart"', false)
                ->assertDontSee('id="product-size-chart"', false)->assertDontSee('relaxed unisex fit');
        }
        Http::assertNothingSent();
    }

    public function test_out_of_stock_sizes_keep_the_guide_and_retired_or_unsynced_variants_do_not_choose_it()
    {
        $product = $this->product([586, 71, null]);
        $variants = $product->variants;
        $variants[0]['out_of_stock'] = true;
        $variants[1]['discontinued'] = true;
        $variants[2]['synced'] = false;
        $product->variants = $variants;
        $this->assertSame('Comfort Colors 1717', SizeCharts::forProduct($product)['name']);
        $variants[0]['synced'] = false;
        $product->variants = $variants;
        $this->assertNull(SizeCharts::forProduct($product));
    }
}
