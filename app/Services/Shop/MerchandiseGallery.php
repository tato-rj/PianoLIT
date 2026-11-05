<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;

/** Manual web gallery curation, independent of provider imports and sellable variants. */
class MerchandiseGallery
{
    public function tabId(string $color): string
    {
        return 'mockup-color-'.substr(hash('sha256', $color), 0, 12);
    }

    public function colors(MerchandiseProduct $product): array
    {
        return collect($product->variants ?? [])->map(fn ($variant) => ($variant['color'] ?? null) ?: 'Original')->unique()->values()->all();
    }

    public function groups(MerchandiseProduct $product): array
    {
        $colors = $this->colors($product);
        $uploads = $product->images->where('source', 'upload');
        $keys = array_unique(array_merge([''], $colors, $uploads->pluck('gallery_color')->filter()->all()));
        return array_map(function ($color) use ($uploads, $colors) {
            return ['color' => $color, 'label' => $color === '' ? 'Unassigned mockups' : $color,
                'offered' => $color === '' || in_array($color, $colors, true),
                'images' => $uploads->filter(fn ($image) => ($image->gallery_color ?? '') === $color)->values()];
        }, array_values($keys));
    }

    public function present(MerchandiseProduct $product, array $variants, $images): array
    {
        $main = $images->first();
        $selected = collect($variants)->first(fn ($variant) => $main && $variant['color'] === $main->gallery_color) ?? ($variants[0] ?? null);
        $color = $selected['color'] ?? ($main->gallery_color ?? ($this->colors($product)[0] ?? null));
        // Products without named colors retain their existing single gallery.
        $namedColors = collect($product->variants ?? [])->contains(fn ($variant) => !empty($variant['color']));
        $views = [];
        $gallery = $images->filter(fn ($image) => $image->gallery_color !== null || !$namedColors)
            ->values()->map(function ($image) use ($product, $color, &$views) {
                $key = $image->gallery_color ?? '';
                $views[$key] = ($views[$key] ?? 0) + 1;
                return ['url' => $image->url, 'alt' => $this->description($product, $image->gallery_color, $views[$key]), 'color' => $image->gallery_color ?? $color];
            });
        $galleryColors = $gallery->pluck('color')->intersect(array_column($variants, 'color'))->unique()->values()->all();
        if (!$selected || !in_array($selected['color'], $galleryColors, true)) {
            $selected = collect($variants)->first(fn ($variant) => in_array($variant['color'], $galleryColors, true));
        }
        if ($selected) $color = $selected['color'];
        return ['gallery' => $gallery, 'galleryColor' => $color, 'galleryColors' => $galleryColors, 'selectedVariant' => $selected];
    }

    public function description(MerchandiseProduct $product, ?string $color, int $view): string
    {
        return $product->name.($color !== null ? ' — '.$color : '').' · View '.$view;
    }
}
