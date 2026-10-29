<?php

namespace App\Services\Shop;

use App\Merchandise\MerchandiseProduct;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/** Name/URL validation for web administration, without changing mobile models. */
class ProductNames
{
    public static function slug(string $name): string
    {
        return rtrim(mb_substr(Str::slug(trim($name)), 0, 200), '-');
    }

    public function validate($name, ?Model $existing = null, ?string $storeId = null, string $field = 'title', string $bag = 'default'): string
    {
        $name = is_string($name) ? trim($name) : $name;
        Validator::make([$field => $name], [$field => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($existing, $storeId) {
            if (!is_string($value)) return;
            $slug = self::slug($value);
            if ($slug === '') {
                $fail('Use a product name that can form a readable URL.');
                return;
            }
            foreach ([eBook::class => 'title', eScore::class => 'title', MerchandiseProduct::class => 'name'] as $model => $column) {
                $query = $model === MerchandiseProduct::class
                    ? $model::withTrashed()->where('printful_store_id', $storeId ?? app(Storefront::class)->storeId())
                    : $model::query();
                if ($existing && get_class($existing) === $model) $query->where('id', '<>', $existing->getKey());
                if ($query->where(function ($query) use ($column, $value, $slug) {
                    $query->whereRaw('LOWER(TRIM('.$column.')) = ?', [mb_strtolower($value)])->orWhere('slug', $slug);
                })->exists()) {
                    $fail('Another product already uses this name or URL. Please choose a unique product name.');
                    return;
                }
            }
        }]])->validateWithBag($bag);
        return $name;
    }
}
