<?php

namespace App\Services\Shop;

class EmailProducts
{
    public function items(array $items, array $orderItems = []): array
    {
        return array_map(function ($item) use ($orderItems) {
            $image = $this->imageUrl($item['image'] ?? null);
            if (!$image && $orderItems) {
                // Historical package snapshots contain names/options, but no image.
                // Match one purchased variant without changing the package quantity.
                $matches = array_values(array_filter($orderItems, fn ($saved) =>
                    ($saved['name'] ?? null) === ($item['name'] ?? null)
                    && ($saved['option'] ?? null) === ($item['option'] ?? null)));
                if (count($matches) === 1) $image = $this->imageUrl($matches[0]['image'] ?? null);
            }
            $item['image'] = $image;
            return $item;
        }, $items);
    }

    private function imageUrl($value): ?string
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($value);
        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !isset($parts['user']) && !isset($parts['pass']) ? $value : null;
    }
}
