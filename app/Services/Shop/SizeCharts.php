<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;

/** Verified supplier guides for web presentation only; no provider requests. */
class SizeCharts
{
    public static function forProduct(MerchandiseProduct $product): ?array
    {
        $variants = collect($product->variants ?? [])->filter(fn ($variant) => ($variant['synced'] ?? false) === true
            && ($variant['discontinued'] ?? false) !== true);
        $catalogIds = $variants->pluck('catalog_product_id')->unique()->values();
        if ($catalogIds->count() !== 1 || !$catalogIds->first()) return null;

        return self::catalog((int) $catalogIds->first());
    }

    public static function catalog(int $id): ?array
    {
        if ($id !== 586) return null;

        // Printful product measurements, checked November 5, 2026. Keep both
        // published units: supplier centimeter rounding differs from conversion.
        return [
            'name' => 'Comfort Colors 1717',
            'source' => 'https://www.printful.com/custom/mens/all/unisex-garment-dyed-heavyweight-shirt-comfort-colors-1717',
            'fitting' => 'This T-shirt has a relaxed unisex fit. Compare the garment measurements with a T-shirt you already wear to choose a size.',
            'rows' => [
                'S' => ['in' => ['26 5/8', '18 1/4', '16 1/4'], 'cm' => ['67.6', '46.4', '41.3']],
                'M' => ['in' => ['28', '20 1/4', '17 3/4'], 'cm' => ['71', '51.4', '45']],
                'L' => ['in' => ['29 3/8', '22', '19'], 'cm' => ['74.6', '56', '48.3']],
                'XL' => ['in' => ['30 3/4', '24', '20 1/2'], 'cm' => ['78', '61', '52']],
                '2XL' => ['in' => ['31 5/8', '26', '21 3/4'], 'cm' => ['80.3', '66', '55.2']],
                '3XL' => ['in' => ['32 1/2', '27 3/4', '23 1/4'], 'cm' => ['82.6', '70.5', '59']],
                '4XL' => ['in' => ['33 1/2', '29 3/4', '24 5/8'], 'cm' => ['85', '75.6', '62.6']],
            ],
        ];
    }
}
