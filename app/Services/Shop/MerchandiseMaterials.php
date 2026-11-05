<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;
use Illuminate\Support\Facades\DB;

/** Provider composition stays separate from shared/mobile product serialization. */
class MerchandiseMaterials
{
    public static function composition($value): ?array
    {
        if (!is_array($value) || count($value) > 20) return null;
        if (!$value) return [];
        $result = [];
        $names = [];
        foreach ($value as $part) {
            if (!is_array($part) || !is_string($part['name'] ?? null)) return null;
            $name = trim($part['name']);
            $percentage = $part['percentage'] ?? null;
            if ($name === '' || mb_strlen($name) > 80 || preg_match('/[<>\x00-\x1f\x7f]/u', $name)
                || (!is_int($percentage) && !is_float($percentage)) || !is_finite((float) $percentage)
                || $percentage <= 0 || $percentage > 100 || isset($names[mb_strtolower($name)])) return null;
            $names[mb_strtolower($name)] = true;
            $result[] = ['name' => $name, 'percentage' => round($percentage, 3)];
        }
        // An incomplete or malformed blend must not become a misleading fabric claim.
        if (abs(array_sum(array_column($result, 'percentage')) - 100) > 0.1) return null;
        usort($result, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        return $result;
    }

    private function matches($saved, array $variant): bool
    {
        return $saved && (int) $saved->catalog_variant_id === ($variant['variant_id'] ?? null)
            && (empty($variant['catalog_product_id']) || (int) $saved->catalog_product_id === $variant['catalog_product_id'])
            && $saved->color === ($variant['color'] ?? null) && $saved->size === ($variant['size'] ?? null);
    }

    /** Called inside the import transaction while the owning product is locked. */
    public function sync(MerchandiseProduct $product, array $variants, array $catalogRows): void
    {
        $previous = DB::table('merchandise_materials')->where('product_id', $product->id)->get()->keyBy('sync_variant_id');
        $rows = [];
        foreach ($variants as $variant) {
            if (!$variant['synced'] || $variant['variant_id'] < 1) continue;
            $catalog = $catalogRows[$variant['id']] ?? null;
            $composition = self::composition($catalog['material'] ?? null);
            $old = $previous->get($variant['id']);
            $description = null;
            if ($composition === [] || (is_array($catalog) && ($catalog['material'] ?? null) === null && is_string($catalog['catalog_description'] ?? null))) {
                $description = MaterialDescription::extract($catalog['catalog_description'] ?? null);
                if ($description !== null) $composition = [];
                if ($description === null && $this->matches($old, $variant)) $description = $old->description_text ?? null;
            }
            if ($composition === null && $this->matches($old, $variant)) {
                $composition = self::composition(json_decode($old->composition, true));
                $description = $old->description_text ?? null;
            }
            if (!$composition && !$description) continue;
            $rows[] = [
                'product_id' => $product->id, 'sync_variant_id' => $variant['id'],
                'catalog_variant_id' => $variant['variant_id'],
                'catalog_product_id' => $catalog['product_id'] ?? $old->catalog_product_id,
                'color' => $variant['color'], 'size' => $variant['size'],
                'composition' => json_encode($composition ?: []), 'description_text' => $composition ? null : $description, 'updated_at' => now(),
            ];
        }
        DB::table('merchandise_materials')->where('product_id', $product->id)->delete();
        if ($rows) DB::table('merchandise_materials')->insert($rows);
    }

    public function forProduct(MerchandiseProduct $product, array $variants): array
    {
        if ($product->materials_override !== null) {
            return $product->materials_override === '' ? [] : [['label' => null, 'text' => $product->materials_override]];
        }
        return $this->importedForProduct($product, $variants);
    }

    public static function plainText(array $materials): string
    {
        return implode("\n", array_map(fn ($material) => ($material['label'] ? $material['label'].': ' : '').$material['text'], $materials));
    }

    public function importedForProduct(MerchandiseProduct $product, array $variants): array
    {
        $saved = DB::table('merchandise_materials')->where('product_id', $product->id)->get()->keyBy('sync_variant_id');
        $original = collect($product->variants ?? [])->keyBy('id');
        $options = [];
        foreach (collect($variants)->groupBy('color') as $color => $choices) {
            $texts = $choices->map(function ($variant) use ($saved, $original) {
                $row = $saved->get($variant['id']);
                $raw = $original->get($variant['id']);
                $parts = $raw && $this->matches($row, $raw) ? self::composition(json_decode($row->composition, true)) : null;
                if ($parts) return implode(', ', array_map(fn ($part) => rtrim(rtrim(number_format($part['percentage'], 3, '.', ''), '0'), '.').'% '.$part['name'], $parts));
                $text = $raw && $this->matches($row, $raw) ? ($row->description_text ?? null) : null;
                return is_string($text) && mb_strlen($text) <= MaterialDescription::MAX_LENGTH && trim($text) !== '' ? $text : null;
            });
            if ($texts->unique()->count() === 1) $options[$color] = $texts->first();
            else foreach ($choices->values() as $index => $choice) $options[$color.' / '.$choice['size']] = $texts->values()[$index];
        }
        if (!array_filter($options, fn ($text) => $text !== null)) return [];
        if (count(array_unique(array_values($options), SORT_REGULAR)) === 1) return [['label' => null, 'text' => reset($options)]];
        return collect($options)->map(fn ($text, $label) => ['label' => $label, 'text' => $text ?? 'Material details are not available for this option.'])->values()->all();
    }
}
