<?php

namespace App\Services\Shop;

use App\Merchandise\{MerchandiseProduct, Product};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;

/** Public catalog of imported merchandise and published downloads. Never calls Printful. */
class Storefront
{
    const DEMO_STORE_ID = 0;
    const CATEGORIES = ['' => 'All', 't-shirts' => 'T-shirts', 'stickers' => 'Stickers', 'ebooks' => 'eBooks', 'escores' => 'eScores'];
    const SECTIONS = ['t-shirts' => 'T-shirts', 'stickers' => 'Stickers', 'escores' => 'eScores', 'ebooks' => 'eBooks', 'merchandise' => 'Merchandise'];

    public function storeId(): string
    {
        return app()->environment('local') ? (string) self::DEMO_STORE_ID : (string) config('services.printful.store_id');
    }

    public function query()
    {
        return MerchandiseProduct::where('printful_store_id', $this->storeId());
    }

    public function products(string $search, ?string $category, array $topics = []): Collection
    {
        return $this->cards($this->matches($search, $category, $topics));
    }

    public function grouped(string $search): Collection
    {
        $groups = $this->products($search, null)->groupBy('category');
        return collect(self::SECTIONS)->map(fn ($label, $category) => $groups->get($category, collect()));
    }

    private function matches(string $search, ?string $category, array $topics = []): Collection
    {
        // Keep model types distinct: numeric IDs can overlap between all three tables.
        $matches = collect();
        if (!$category || in_array($category, ['t-shirts', 'stickers', 'merchandise'], true)) {
            $merchandise = $this->matching($this->query(), 'name', $search)->get()
                ->filter(fn ($product) => !$category || $this->category($product) === $category);
            $matches = $matches->concat($merchandise);
        }
        foreach (['ebooks' => eBook::class, 'escores' => eScore::class] as $key => $model) {
            if (!$category || $category === $key) {
                $query = $this->matching($model::published(), 'title', $search);
                if ($topics) $query->whereHas('topics', fn ($query) => $query->whereIn('slug', $topics));
                $matches = $matches->concat($query->get());
            }
        }
        return $matches->sort(fn ($a, $b) => ($b->created_at <=> $a->created_at)
            ?: strcmp(get_class($a), get_class($b)) ?: ($b->id <=> $a->id))->values();
    }

    private function cards(Collection $products): Collection
    {
        (new ModelCollection($products->whereInstanceOf(MerchandiseProduct::class)->all()))->load('images');
        return $products->map(fn ($product) => $product instanceof MerchandiseProduct ? $this->present($product) : $this->presentDigital($product));
    }

    private function matching($query, string $column, string $search)
    {
        if ($search !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%';
            $query->whereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$like]);
        }
        return $query;
    }

    public function presentDigital(Product $product): array
    {
        $category = $product instanceof eBook ? 'ebooks' : 'escores';
        return [
            'id' => $product->id, 'name' => $product->title, 'category' => $category, 'digital' => true,
            'label' => $category === 'ebooks' ? 'eBook' : 'eScore', 'url' => $product->showRoute(),
            'imageUrl' => $product->mockup_image() ?: ($product->cover_path ? $product->cover_image() : null),
            'variants' => [], 'price' => $product->isFree() ? 'Free' : $this->money((int) $product->finalPrice(true), 'USD'),
            'priceVaries' => false,
        ];
    }

    public function variants(MerchandiseProduct $product): array
    {
        $variants = [];
        foreach ($product->variants ?? [] as $variant) {
            if (empty($variant['id']) || ($variant['synced'] ?? false) !== true
                || ($variant['discontinued'] ?? false) === true || ($variant['out_of_stock'] ?? false) === true
                || !preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', (string) ($variant['retail_price'] ?? ''))
                || !preg_match('/^[A-Z]{3}$/D', (string) ($variant['currency'] ?? ''))) continue;
            $parts = explode('.', (string) $variant['retail_price']);
            $cents = ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
            if ($cents <= 0) continue;
            $variants[] = [
                'id' => (int) $variant['id'], 'size' => ($variant['size'] ?? null) ?: 'One size',
                'color' => ($variant['color'] ?? null) ?: 'Original', 'cents' => $cents,
                'currency' => $variant['currency'], 'price' => $this->money($cents, $variant['currency']),
            ] + MerchandiseColors::codes($variant);
        }
        return $variants;
    }

    public function category(MerchandiseProduct $product): string
    {
        $names = implode(' ', array_filter(array_column($product->variants ?? [], 'product_name')));
        // Prefer the provider type, then support older imports with explicit names.
        foreach ([$names, $product->name] as $name) {
            if (preg_match('/\bstickers?\b/i', $name)) return 'stickers';
            if (preg_match('/\b(t[ -]?shirts?|tees?)\b/i', $name)) return 't-shirts';
        }
        return 'merchandise';
    }

    public function present(MerchandiseProduct $product): array
    {
        $variants = $this->variants($product);
        $images = $product->images->sortBy(fn ($image) => $image->id == $product->main_image_id ? -1 : $image->position)->values();
        $currencies = array_unique(array_column($variants, 'currency'));
        $price = count($currencies) === 1 ? min(array_column($variants, 'cents')) : null;
        $category = $this->category($product);
        return [
            'id' => $product->id, 'name' => $product->name, 'category' => $category,
            'label' => ['t-shirts' => 'T-shirt', 'stickers' => 'Sticker', 'merchandise' => 'Merchandise'][$category],
            'url' => $this->productUrl($product), 'images' => $images, 'image' => $images->first(),
            'imageUrl' => optional($images->first())->url, 'digital' => false,
            'variants' => $variants, 'colors' => MerchandiseColors::options($variants), 'price' => $price === null ? null : $this->money($price, reset($currencies)),
            'priceVaries' => count(array_unique(array_column($variants, 'cents'))) > 1,
        ];
    }

    public function categoryUrl(?string $category): string
    {
        if (!$category) return route('shop.home');
        if (in_array($category, ['ebooks', 'escores'], true)) return route($category.'.index');
        return route('shop.category', ['category' => $category]);
    }

    public function productUrl(MerchandiseProduct $product): string
    {
        return route('shop.merchandise.show', ['category' => $this->category($product), 'slug' => $product->slug]);
    }

    public function money(int $cents, string $currency): string
    {
        $prefix = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'][$currency] ?? $currency.' ';
        return $prefix.number_format($cents / 100, 2, '.', ',').($currency === 'USD' ? ' USD' : '');
    }
}
