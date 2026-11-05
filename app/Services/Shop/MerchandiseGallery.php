<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;

/** Manual web gallery curation, independent of provider imports and sellable variants. */
class MerchandiseGallery
{
    public function colors(MerchandiseProduct $product): array
    {
        return collect($product->variants ?? [])->map(fn ($variant) => ($variant['color'] ?? null) ?: 'Original')->unique()->values()->all();
    }

    public function groups(MerchandiseProduct $product): array
    {
        $colors = $this->colors($product);
        $keys = array_unique(array_merge([''], $colors, $product->images->pluck('gallery_color')->filter()->all()));
        return array_map(function ($color) use ($product, $colors) {
            return ['color' => $color, 'label' => $color === '' ? 'Unassigned mockups' : $color,
                'offered' => $color === '' || in_array($color, $colors, true),
                'images' => $product->images->filter(fn ($image) => ($image->gallery_color ?? '') === $color)->values()];
        }, array_values($keys));
    }

    public function present(MerchandiseProduct $product, array $variants, $images): array
    {
        $main = $images->first();
        $selected = collect($variants)->first(fn ($variant) => $main && $variant['color'] === $main->gallery_color) ?? ($variants[0] ?? null);
        $color = $selected['color'] ?? ($main->gallery_color ?? ($this->colors($product)[0] ?? null));
        // Products without named colors retain their existing single gallery.
        $namedColors = collect($product->variants ?? [])->contains(fn ($variant) => !empty($variant['color']));
        $gallery = $images->filter(fn ($image) => $image->gallery_color !== null || !$namedColors)
            ->values()->map(fn ($image) => ['url' => $image->url, 'alt' => $image->alt, 'color' => $image->gallery_color ?? $color]);
        return ['gallery' => $gallery, 'galleryColor' => $color, 'selectedVariant' => $selected];
    }
}
