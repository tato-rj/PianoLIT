<?php

namespace App\Services\WebApp;

use Illuminate\Database\Eloquent\Collection;

class PieceCards
{
    // Keep these presentation-only attributes out of shared feeds and mobile JSON.
    public static function load($pieces, $favorites = true)
    {
        $pieces = new Collection(collect($pieces)->all());
        if ($pieces->isEmpty()) return $pieces;

        $pieces->loadMissing(['tags', 'composer']);
        $relations = [
            'performances as webapp_has_performances' => function ($query) { $query->approved(); },
            'tutorials as webapp_has_synthesia' => function ($query) { $query->where('category', 'synthesia'); },
        ];
        if ($favorites && auth('web')->check()) {
            $relations['favorites as webapp_is_favorited'] = function ($query) {
                $query->where('user_id', auth('web')->id());
            };
        }
        $pieces->loadExists($relations);

        return $pieces;
    }
}
