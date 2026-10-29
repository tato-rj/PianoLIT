<?php

namespace App\Services\Admin;

use App\Merchandise\{MerchandiseImage, MerchandiseProduct};
use App\Services\Shop\{MerchandiseColors, MerchandiseImages, Printful, PrintfulException, ProductNames};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchandiseCatalog
{
    public function import(int $id, ?MerchandiseProduct $existing = null, ?int $revision = null): array
    {
        $api = app(Printful::class);
        $storeId = $api->storeId();
        if ($existing && (string) $existing->printful_store_id !== $storeId) {
            throw new PrintfulException('This product belongs to another Printful store.');
        }
        if ($existing) $this->checkRevision($existing, $revision);
        if (!$existing && $found = MerchandiseProduct::withTrashed()->where('printful_store_id', $storeId)->where('printful_product_id', $id)->first()) {
            if (!$found->trashed()) return [$found, 0, 0];
            // Explicit re-import restores curation only after Printful returns it.
            $existing = $found;
            $revision = $found->revision;
        }
        $remote = $api->product($id);
        $name = $remote['sync_product']['name'] ?? null;
        if ((string) ($remote['sync_product']['id'] ?? '') !== (string) $id || !is_string($name) || trim($name) === ''
            || mb_strlen($name) > 255 || !isset($remote['sync_variants']) || !is_array($remote['sync_variants']) || count($remote['sync_variants']) > 1000) {
            throw new PrintfulException('Printful returned incomplete product details. Nothing was changed.');
        }
        try {
            $name = app(ProductNames::class)->validate($name, $existing, $storeId, 'name', 'merchandise');
        } catch (ValidationException $exception) {
            throw new PrintfulException('This Printful product needs a unique name before it can be imported or refreshed. '.collect($exception->errors())->flatten()->first());
        }
        $slug = $existing && $existing->slug ? $existing->slug : ProductNames::slug($name);
        $variants = $this->variants($remote['sync_variants']);
        [$variants, $colorWarnings] = app(MerchandiseColors::class)->enrich($variants, $existing ? ($existing->variants ?? []) : []);
        $candidates = $this->mockups($remote);
        $known = $existing ? $existing->images()->withTrashed()->whereNotNull('source_key')->pluck('source_key')->all() : [];
        $assets = [];
        $warnings = 0;
        $images = app(MerchandiseImages::class);
        $deadline = microtime(true) + 12;
        try {
            foreach ($candidates as $url) {
                if (in_array(hash('sha256', $url), $known, true)) continue;
                if (!$images->allowedUrl($url) || count($assets) >= MerchandiseImages::MAX_IMAGES || microtime(true) >= $deadline) { $warnings++; continue; }
                try {
                    $assets[] = $images->fromPrintful($url, $name, max(1, min(5, (int) ceil($deadline - microtime(true)))));
                } catch (PrintfulException $exception) {
                    $warnings++;
                }
            }
            $usedPaths = [];
            $product = DB::transaction(function () use ($existing, $revision, $id, $storeId, $name, $slug, $variants, $assets, &$usedPaths, &$warnings) {
                if (!$existing) {
                    $inserted = DB::table('merchandise_products')->insertOrIgnore([
                        'printful_store_id' => $storeId, 'printful_product_id' => $id, 'name' => $name, 'slug' => $slug,
                        'variants' => json_encode($variants), 'revision' => 1, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $product = MerchandiseProduct::withTrashed()->where('printful_store_id', $storeId)->where('printful_product_id', $id)->lockForUpdate()->first();
                if (!$product) throw new PrintfulException('Another product already uses this URL. Please choose a unique name in Printful and try again.');
                // A concurrent first import already completed; preserve that gallery.
                if (!$existing && !$inserted) return $product;
                if ($existing) $this->checkRevision($product, $revision);
                if ($product->trashed()) $product->restore();
                $position = (int) $product->images()->withTrashed()->max('position');
                $activeCount = $product->images()->count();
                foreach ($assets as $asset) {
                    if ($product->images()->withTrashed()->where(function ($query) use ($asset) {
                        $query->where('source_key', $asset['source_key'])->orWhere('checksum', $asset['checksum']);
                    })->exists()) continue;
                    if ($activeCount >= MerchandiseImages::MAX_IMAGES) { $warnings++; continue; }
                    $image = $product->images()->create($asset + ['position' => ++$position]);
                    $usedPaths[] = $asset['path'];
                    $activeCount++;
                    if (!$product->main_image_id) $product->main_image_id = $image->id;
                }
                $product->fill(['name' => $name, 'variants' => $variants, 'synced_at' => now(), 'revision' => $product->revision + 1])->save();
                return $product;
            });
        } catch (\Throwable $exception) {
            $images->discard($assets);
            throw $exception;
        }
        $images->discard(array_filter($assets, fn ($asset) => !in_array($asset['path'], $usedPaths, true)));
        return [$product, $warnings, $colorWarnings];
    }

    private function variants(array $variants): array
    {
        $result = [];
        foreach ($variants as $variant) {
            if (!is_array($variant) || !is_numeric($variant['id'] ?? null) || (int) $variant['id'] < 1 || !is_numeric($variant['variant_id'] ?? null)) {
                throw new PrintfulException('Printful returned incomplete variant details. Nothing was changed.');
            }
            $row = ['id' => (int) $variant['id'], 'variant_id' => (int) $variant['variant_id'], 'synced' => ($variant['synced'] ?? false) === true];
            foreach (['name', 'sku', 'size', 'color'] as $key) $row[$key] = is_string($variant[$key] ?? null) ? mb_substr($variant[$key], 0, 255) : null;
            // The provider's garment/product name identifies a T-shirt or sticker
            // even when the merchant's design title doesn't mention its type.
            $row['product_name'] = is_string($variant['product']['name'] ?? null) ? mb_substr($variant['product']['name'], 0, 255) : null;
            $catalogId = $variant['product']['product_id'] ?? null;
            $row['catalog_product_id'] = is_int($catalogId) && $catalogId > 0 ? $catalogId : null;
            $price = $variant['retail_price'] ?? null;
            $row['retail_price'] = is_scalar($price) && preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', (string) $price) ? (string) $price : null;
            $row['currency'] = is_string($variant['currency'] ?? null) && preg_match('/^[A-Z]{3}$/D', $variant['currency']) ? $variant['currency'] : null;
            foreach (['discontinued', 'out_of_stock'] as $key) $row[$key] = is_bool($variant[$key] ?? null) ? $variant[$key] : null;
            $result[] = $row;
        }
        return $result;
    }

    private function mockups(array $remote): array
    {
        $urls = [];
        if (is_string($remote['sync_product']['thumbnail_url'] ?? null)) $urls[] = $remote['sync_product']['thumbnail_url'];
        foreach ($remote['sync_variants'] as $variant) {
            foreach (is_array($variant['files'] ?? null) ? $variant['files'] : [] as $file) {
                // Print-file previews are artwork, not product mockups.
                if (!is_array($file) || ($file['type'] ?? '') !== 'preview') continue;
                $url = $file['preview_url'] ?? $file['thumbnail_url'] ?? null;
                if (is_string($url) && $url !== '') $urls[] = $url;
            }
        }
        return array_values(array_unique($urls));
    }

    public function change(MerchandiseProduct $product, int $revision, callable $change): MerchandiseProduct
    {
        return DB::transaction(function () use ($product, $revision, $change) {
            $locked = MerchandiseProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->checkRevision($locked, $revision);
            $change($locked);
            $locked->revision++;
            $locked->save();
            return $locked;
        });
    }

    private function checkRevision(MerchandiseProduct $product, ?int $revision): void
    {
        if ($product->revision !== $revision) {
            throw ValidationException::withMessages(['revision' => 'This product changed in another tab. Reload the page and try again.'])->errorBag('merchandise');
        }
    }

    public function upload(MerchandiseProduct $product, int $revision, array $files, ?string $alt): void
    {
        $this->checkRevision($product, $revision);
        $images = app(MerchandiseImages::class);
        $assets = [];
        $usedPaths = [];
        try {
            foreach ($files as $file) $assets[] = $images->fromUpload($file, $alt ?: $product->name);
            $this->change($product, $revision, function ($locked) use ($assets, &$usedPaths) {
                $count = $locked->images()->count();
                $position = (int) $locked->images()->withTrashed()->max('position');
                foreach ($assets as $asset) {
                    $existing = $locked->images()->withTrashed()->where('checksum', $asset['checksum'])->first();
                    if ($existing && !$existing->trashed()) continue;
                    if ($count >= MerchandiseImages::MAX_IMAGES) throw new PrintfulException('A product can have up to 40 gallery images. Remove an image before uploading more.');
                    if ($existing) {
                        $existing->restore();
                        $existing->update(['alt' => $asset['alt'], 'position' => ++$position]);
                        $image = $existing;
                    } else {
                        $image = $locked->images()->create($asset + ['position' => ++$position]);
                        $usedPaths[] = $asset['path'];
                    }
                    if (!$locked->main_image_id) $locked->main_image_id = $image->id;
                    $count++;
                }
            });
        } catch (\Throwable $exception) {
            $images->discard($assets);
            throw $exception;
        }
        $images->discard(array_filter($assets, fn ($asset) => !in_array($asset['path'], $usedPaths, true)));
    }

    public function image(MerchandiseProduct $product, int $id, bool $removed = false): MerchandiseImage
    {
        return ($removed ? $product->images()->onlyTrashed() : $product->images())->whereKey($id)->firstOrFail();
    }
}
