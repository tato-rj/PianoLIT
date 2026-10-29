<?php

namespace App\Console\Commands;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{MerchandiseColors, Storefront};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};

class SeedDemoMerchandise extends Command
{
    protected $signature = 'shop:seed-demo {--refresh-colors : Update only bundled sample color metadata}';
    protected $description = 'Add local-only sample merchandise without contacting Printful';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('Sample merchandise can only be created in the local environment.');
            return 1;
        }
        $samples = [
            // Offline fixture colors exercise the same stored fields as real catalog imports.
            [1001, 'Piano rainbow T-shirt · Burgundy', 'shirt-burgundy.png', '28.00', 'Burgundy', '#8b2633'],
            [1002, 'Piano rainbow T-shirt · Heather', 'shirt-heather.png', '30.00', 'Heather grey', '#9e9e9f'],
            [1003, 'Piano rainbow T-shirt · Mint', 'shirt-mint.png', '28.00', 'Mint', '#b9d8d1'],
            [1004, 'Piano rainbow T-shirt · Butter', 'shirt-butter.png', '32.00', 'Butter yellow', '#f5cf89'],
            [1005, 'More piano sticker', 'piano-sticker.svg', '5.00', 'Original', null],
            [1006, 'Play it again sticker', 'play-sticker.svg', '5.00', 'Original', null],
        ];
        $created = 0;
        foreach ($samples as [$id, $name, $file, $price, $color, $colorCode]) {
            $existing = MerchandiseProduct::withTrashed()->where('printful_store_id', Storefront::DEMO_STORE_ID)->where('printful_product_id', $id)->first();
            if ($existing) {
                if ($this->option('refresh-colors') && !$existing->trashed()) {
                    DB::transaction(function () use ($existing, $id, $color, $colorCode) {
                        $product = MerchandiseProduct::whereKey($existing->id)->lockForUpdate()->firstOrFail();
                        $variants = $product->variants ?? [];
                        foreach ($variants as &$variant) {
                            if (($variant['variant_id'] ?? null) === 0 && ($variant['color'] ?? null) === $color
                                && in_array($variant['id'] ?? null, range($id * 10, $id * 10 + 4), true)) {
                                $variant = array_merge($variant, MerchandiseColors::codes(['color_code' => $colorCode]));
                            }
                        }
                        unset($variant);
                        if ($variants !== $product->variants) $product->update(['variants' => $variants, 'revision' => $product->revision + 1]);
                    });
                }
                continue;
            }
            $sticker = strpos($file, 'sticker') !== false;
            $variants = [];
            foreach ($sticker ? ['3 × 3 in', '4 × 4 in'] : ['XS', 'S', 'M', 'L', 'XL'] as $i => $size) {
                $variants[] = ['id' => $id * 10 + $i, 'variant_id' => 0, 'synced' => true,
                    'name' => $name.' / '.$size, 'size' => $size, 'color' => $color, 'sku' => 'DEMO-'.$id.'-'.$i,
                    'product_name' => $sticker ? 'Sticker' : 'T-shirt', 'retail_price' => $sticker && $i === 1 ? '7.00' : $price,
                    'currency' => 'USD', 'discontinued' => false, 'out_of_stock' => false] + MerchandiseColors::codes(['color_code' => $colorCode]);
            }
            $files = $sticker ? [$file] : array_values(array_unique([$file, 'shirt-burgundy.png', 'shirt-heather.png', 'shirt-mint.png', 'shirt-butter.png']));
            $assets = [];
            foreach ($files as $position => $asset) {
                $bytes = file_get_contents(resource_path('images/shop/demo/'.$asset));
                $path = 'shop/demo/'.$asset;
                if (!Storage::disk('public')->put($path, $bytes)) throw new \RuntimeException('Could not write a sample image.');
                $assets[] = ['disk' => 'public', 'path' => $path, 'source' => 'upload', 'checksum' => hash('sha256', $bytes),
                    'alt' => $name.' · sample view '.($position + 1), 'position' => $position + 1, 'width' => 600, 'height' => 600];
            }
            DB::transaction(function () use ($id, $name, $variants, $assets) {
                $product = MerchandiseProduct::create(['printful_store_id' => Storefront::DEMO_STORE_ID,
                    'printful_product_id' => $id, 'name' => $name, 'variants' => $variants, 'revision' => 1]);
                foreach ($assets as $asset) {
                    $image = $product->images()->create($asset);
                    if (!$product->main_image_id) $product->main_image_id = $image->id;
                }
                $product->save();
            });
            $created++;
        }
        $this->info($created.' local sample products added. Printful was not contacted. Existing samples and image choices were preserved.');
        if ($this->option('refresh-colors')) $this->info('Bundled sample color metadata refreshed; other product data and images were preserved.');
        return 0;
    }
}
