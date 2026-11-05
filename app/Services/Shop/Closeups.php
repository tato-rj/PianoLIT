<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

/** Optional, independently uploaded web artwork. Never changes shared product attributes. */
class Closeups
{
    const PRODUCTS = ['merchandise' => MerchandiseProduct::class, 'ebooks' => eBook::class, 'escores' => eScore::class];

    public function for(Model $product)
    {
        return DB::table('shop_product_closeups')->where($this->owner($product))->first();
    }

    public function url($record): ?string
    {
        if (!$record || $record->disk !== 'public' || !is_string($record->path)
            || !preg_match('#^shop/closeups/[a-f0-9-]{36}\.(?:jpg|png)$#D', $record->path)
            || !Storage::disk('public')->exists($record->path)) return null;
        // These new local raster assets use the same public storage mount on every hub.
        return asset('storage/'.$record->path);
    }

    public function urlsFor(Collection $products): Collection
    {
        if ($products->isEmpty()) return collect();
        return DB::table('shop_product_closeups')->whereNotNull('path')->where(function ($query) use ($products) {
            foreach ($products->groupBy(fn ($product) => get_class($product)) as $type => $items) {
                $query->orWhere(fn ($query) => $query->where('product_type', $type)->whereIn('product_id', $items->pluck('id')));
            }
        })->get()->mapWithKeys(fn ($record) => [$record->product_type.':'.$record->product_id => $this->url($record)]);
    }

    public function upload(Model $product, int $revision, UploadedFile $file): void
    {
        $images = app(MerchandiseImages::class);
        $asset = $images->fromCloseupUpload($file, ($product->name ?? $product->title).' close-up');
        try {
            $this->change($product, $revision, array_intersect_key($asset, array_flip(['disk', 'path', 'width', 'height', 'checksum'])));
        } catch (\Throwable $exception) {
            $images->discard([$asset]);
            throw $exception;
        }
    }

    public function remove(Model $product, int $revision): void
    {
        $this->change($product, $revision, ['path' => null, 'width' => null, 'height' => null, 'checksum' => null]);
    }

    private function change(Model $product, int $revision, array $attributes): void
    {
        $owner = $this->owner($product);
        DB::transaction(function () use ($product, $owner, $revision, $attributes) {
            $type = get_class($product);
            $query = $type === MerchandiseProduct::class ? app(Storefront::class)->query() : $type::query();
            $query->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $current = DB::table('shop_product_closeups')->where($owner)->lockForUpdate()->first();
            if (($current ? (int) $current->revision : 0) !== $revision) {
                throw ValidationException::withMessages(['closeup_revision' => 'This close-up changed in another tab. Review the current image and try again.'])->errorBag('closeup');
            }
            $values = $attributes + ['revision' => $revision + 1, 'updated_at' => now()];
            if ($current) DB::table('shop_product_closeups')->where('id', $current->id)->update($values);
            else DB::table('shop_product_closeups')->insert($owner + $values + ['created_at' => now()]);
            // Retain previous files, like gallery removals, for cached pages and recovery.
        });
    }

    private function owner(Model $product): array
    {
        if (!in_array(get_class($product), self::PRODUCTS, true)) throw new \InvalidArgumentException('Unsupported close-up product.');
        return ['product_type' => get_class($product), 'product_id' => $product->id];
    }
}
