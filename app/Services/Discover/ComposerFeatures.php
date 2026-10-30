<?php

namespace App\Services\Discover;

/** The composer-feature groups shared by browser Discover and native v2. */
class ComposerFeatures
{
    const GROUPS = [
        'From women composers' => ['key' => 'women', 'label' => 'women'],
        'From black composers' => ['key' => 'black', 'label' => 'Black'],
        'From Latin American composers' => ['key' => 'latin-american', 'label' => 'Latin American'],
        'From American composers' => ['key' => 'american', 'label' => 'American', 'country' => 'us'],
        'From Italian composers' => ['key' => 'italian', 'label' => 'Italian', 'country' => 'it'],
        'From Russian composers' => ['key' => 'russian', 'label' => 'Russian', 'country' => 'ru'],
        'From Ukrainian composers' => ['key' => 'ukrainian', 'label' => 'Ukrainian', 'country' => 'ua'],
    ];

    public static function forTitle(string $title): ?array
    {
        return self::GROUPS[$title] ?? null;
    }

    public static function additionalTitles(): array
    {
        return array_diff(array_keys(self::GROUPS), ['From women composers', 'From black composers']);
    }
}
