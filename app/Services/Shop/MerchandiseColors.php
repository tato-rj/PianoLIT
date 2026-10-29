<?php

namespace App\Services\Shop;

/** Catalog colors are joined by provider identity, never inferred from names or images. */
class MerchandiseColors
{
    public static function codes(array $variant): array
    {
        $codes = [];
        foreach (['color_code', 'color_code2'] as $key) {
            $value = $variant[$key] ?? null;
            $codes[$key] = is_string($value) && preg_match('/^#[a-f0-9]{6}$/iD', $value) ? strtolower($value) : null;
        }
        if (!$codes['color_code']) $codes['color_code2'] = null;
        return $codes;
    }

    public static function options(array $variants): array
    {
        return collect($variants)->groupBy('color')->map(function ($rows) {
            $colors = $rows->map(fn ($row) => self::codes($row))->filter(fn ($codes) => $codes['color_code'])->unique()->values();
            // A shared color name with conflicting provider colors cannot identify one swatch.
            return $colors->count() === 1 ? $colors->first() : [];
        })->all();
    }

    public function enrich(array $variants, array $previous = []): array
    {
        $api = app(Printful::class);
        $known = collect($previous)->keyBy('id');
        $products = [];
        $singleVariants = [];
        $requests = 0;
        $warnings = 0;
        $deadline = microtime(true) + 10;

        foreach ($variants as &$variant) {
            $codes = self::codes([]);
            if (!$variant['synced'] || !$variant['color'] || $variant['variant_id'] < 1) {
                $variant = array_merge($variant, $codes);
                continue;
            }
            $productId = $variant['catalog_product_id'];
            $variantId = $variant['variant_id'];
            $catalog = null;
            try {
                if ($productId) {
                    if (!array_key_exists($productId, $products)) {
                        $products[$productId] = [];
                        if ($requests >= 8 || microtime(true) >= $deadline) throw new PrintfulException('Color lookup budget reached.');
                        $requests++;
                        $response = $api->catalogProduct($productId, (int) ceil($deadline - microtime(true)));
                        if (($response['product']['id'] ?? null) == $productId && is_array($response['variants'] ?? null)) {
                            foreach ($response['variants'] as $row) {
                                if (is_array($row) && ($row['product_id'] ?? null) == $productId && is_int($row['id'] ?? null)) {
                                    $products[$productId][$row['id']] = $row;
                                }
                            }
                        }
                    }
                    $catalog = $products[$productId][$variantId] ?? null;
                } else {
                    // Older/incomplete store responses can omit the blank product identity.
                    if (!array_key_exists($variantId, $singleVariants)) {
                        $singleVariants[$variantId] = null;
                        if ($requests >= 8 || microtime(true) >= $deadline) throw new PrintfulException('Color lookup budget reached.');
                        $requests++;
                        $response = $api->catalogVariant($variantId, (int) ceil($deadline - microtime(true)));
                        $row = $response['variant'] ?? null;
                        if (is_array($row) && ($row['id'] ?? null) === $variantId
                            && is_int($row['product_id'] ?? null) && $row['product_id'] > 0
                            && ($response['product']['id'] ?? null) === $row['product_id']) {
                            $singleVariants[$variantId] = $row;
                        }
                    }
                    $catalog = $singleVariants[$variantId];
                }
            } catch (PrintfulException $exception) {
                // A catalog failure is not a deleted store product, and must not erase saved colors.
            }
            $valid = is_array($catalog) && array_key_exists('color_code', $catalog) && array_key_exists('color_code2', $catalog);
            if ($valid) {
                $codes = self::codes($catalog);
                foreach (['color_code', 'color_code2'] as $key) {
                    if ($catalog[$key] !== null && $codes[$key] === null) $valid = false;
                }
            }
            if (!$valid) {
                $warnings++;
                $old = $known->get($variant['id']);
                $codes = $old && ($old['variant_id'] ?? null) === $variantId && ($old['color'] ?? null) === $variant['color']
                    ? self::codes($old) : self::codes([]);
            } else {
                $variant['catalog_product_id'] = $catalog['product_id'];
            }
            $variant = array_merge($variant, $codes);
        }
        unset($variant);
        return [$variants, $warnings];
    }
}
