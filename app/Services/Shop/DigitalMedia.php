<?php

namespace App\Services\Shop;

use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Web-only artwork and PDF samples; never attached to shared model serialization. */
class DigitalMedia
{
    const PRODUCTS = ['ebooks' => eBook::class, 'escores' => eScore::class];
    const MAX_IMAGES = 40;

    public function owner(Model $product): array
    {
        if (!in_array(get_class($product), self::PRODUCTS, true)) throw new \InvalidArgumentException('Unsupported digital product.');
        return ['product_type' => get_class($product), 'product_id' => $product->id];
    }

    public function record(Model $product)
    {
        return DB::table('shop_digital_media')->where($this->owner($product))->first();
    }

    public function state(Model $product, $record = null): array
    {
        $record = $record ?? $this->record($product);
        // Keep existing artwork available until the manager curates this gallery.
        $images = $record ? json_decode($record->images, true) : collect([$product->mockup_path, $product->cover_path])
            ->filter()->unique()->values()->map(fn ($path) => ['id' => hash('sha256', $path), 'path' => $path, 'removed' => false])->all();
        return ['revision' => $record ? (int) $record->revision : 0, 'images' => $images,
            'main_image_id' => $record ? $record->main_image_id : ($images[0]['id'] ?? null),
            'preview_pages' => $record ? (json_decode($record->preview_pages ?? '[]', true) ?: []) : [],
            'preview_source' => $record->preview_source ?? null];
    }

    public function gallery(Model $product, ?array $state = null): Collection
    {
        $state = $state ?? $this->state($product);
        return collect($state['images'])->reject(fn ($image) => $image['removed'])
            ->sortBy(fn ($image, $index) => $image['id'] === $state['main_image_id'] ? -1 : $index)->values()
            ->map(fn ($image, $index) => ['url' => asset('storage/'.$image['path']), 'alt' => $product->title.' view '.($index + 1)]);
    }

    public function previews(Model $product, ?array $state = null): array
    {
        $state = $state ?? $this->state($product);
        return $product->pdf_path && hash_equals(hash('sha256', $product->pdf_path), $state['preview_source'] ?? '')
            ? array_slice($state['preview_pages'], 0, 7) : [];
    }

    public function mainUrls(Collection $products): Collection
    {
        $digital = $products->filter(fn ($product) => in_array(get_class($product), self::PRODUCTS, true));
        if ($digital->isEmpty()) return collect();
        $records = DB::table('shop_digital_media')->where(function ($query) use ($digital) {
            foreach ($digital->groupBy(fn ($product) => get_class($product)) as $type => $items) {
                $query->orWhere(fn ($query) => $query->where('product_type', $type)->whereIn('product_id', $items->pluck('id')));
            }
        })->get()->keyBy(fn ($record) => $record->product_type.':'.$record->product_id);
        return $digital->mapWithKeys(function ($product) use ($records) {
            $key = get_class($product).':'.$product->id;
            $record = $records->get($key);
            $url = $record ? $this->gallery($product, $this->state($product, $record))->first()['url'] ?? null
                : ($product->mockup_image() ?: ($product->cover_path ? $product->cover_image() : null));
            return [$key => $url];
        });
    }

    public function change(Model $product, int $revision, callable $change): void
    {
        DB::transaction(function () use ($product, $revision, $change) {
            $locked = $product->newQuery()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $owner = $this->owner($locked);
            $state = $this->state($locked);
            if ($state['revision'] !== $revision) {
                throw ValidationException::withMessages(['media_revision' => 'This product changed in another tab. Reload the page and try again.']);
            }
            $state = $change($state, $locked);
            $active = collect($state['images'])->reject(fn ($image) => $image['removed']);
            if (!$active->contains('id', $state['main_image_id'])) $state['main_image_id'] = $active->first()['id'] ?? null;
            DB::table('shop_digital_media')->updateOrInsert($owner, [
                'revision' => $revision + 1, 'images' => json_encode($state['images']), 'main_image_id' => $state['main_image_id'],
                'preview_pages' => json_encode($state['preview_pages']), 'preview_source' => $state['preview_source'],
                'updated_at' => now(), 'created_at' => $this->record($locked)->created_at ?? now(),
            ]);
        });
    }

    public function upload(Model $product, int $revision, array $files): void
    {
        $assets = [];
        try {
            foreach ($files as $file) $assets[] = app(MerchandiseImages::class)->fromDisplayUpload($file, $product->title);
            $this->change($product, $revision, function ($state) use ($assets) {
                if (collect($state['images'])->reject(fn ($image) => $image['removed'])->count() + count($assets) > self::MAX_IMAGES) {
                    throw ValidationException::withMessages(['images' => 'Use up to 40 display images per product. Remove an image before uploading more.']);
                }
                foreach ($assets as $asset) $state['images'][] = ['id' => (string) Str::uuid(), 'path' => $asset['path'], 'removed' => false];
                return $state;
            });
        } catch (\Throwable $exception) {
            app(MerchandiseImages::class)->discard($assets);
            throw $exception;
        }
    }

    public function imageAction(Model $product, int $revision, string $id, string $action): void
    {
        $this->change($product, $revision, function ($state) use ($id, $action) {
            $index = collect($state['images'])->search(fn ($image) => $image['id'] === $id);
            abort_if($index === false, 404);
            $image = $state['images'][$index];
            if ($action === 'restore') {
                abort_unless($image['removed'], 404);
                if (collect($state['images'])->reject(fn ($image) => $image['removed'])->count() >= self::MAX_IMAGES) {
                    throw ValidationException::withMessages(['images' => 'Use up to 40 display images per product. Remove an image before restoring another.']);
                }
                $state['images'][$index]['removed'] = false;
            }
            else {
                abort_if($image['removed'], 404);
                if ($action === 'remove') $state['images'][$index]['removed'] = true;
                elseif ($action === 'main') $state['main_image_id'] = $id;
                elseif (in_array($action, ['up', 'down'], true)) {
                    $keys = collect($state['images'])->reject(fn ($image) => $image['removed'])->keys()->all();
                    $position = array_search($index, $keys, true);
                    $other = $keys[$position + ($action === 'up' ? -1 : 1)] ?? null;
                    if ($other !== null) [$state['images'][$index], $state['images'][$other]] = [$state['images'][$other], $state['images'][$index]];
                } else abort(404);
            }
            return $state;
        });
    }
}
