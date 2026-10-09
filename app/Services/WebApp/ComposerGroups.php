<?php

namespace App\Services\WebApp;

/** Shared web-only groups for Explore guides and the composer directory. */
class ComposerGroups
{
    const OPTIONS = [
        'all' => ['label' => 'All composers', 'icon' => 'users'],
        'women' => ['label' => 'Women composers', 'icon' => 'venus'],
        'living' => ['label' => 'Living composers', 'icon' => 'sprout'],
        'black' => ['label' => 'Black composers', 'icon' => 'users'],
        'latin-american' => ['label' => 'Latin American composers', 'icon' => 'globe'],
        'asian' => ['label' => 'Asian composers', 'icon' => 'globe'],
        'pedagogical' => ['label' => 'Pedagogical composers', 'icon' => 'graduation-cap'],
    ];

    public static function apply($query, $group)
    {
        switch ($group) {
            case 'women':
                return $query->where('gender', 'female');
            case 'living':
                // Match the catalogue's recorded lifespan, excluding unknown/future births.
                return $query->whereNotNull('date_of_birth')->whereDate('date_of_birth', '<=', today())->whereNull('date_of_death');
            case 'black':
            case 'asian':
                return $query->where('ethnicity', $group);
            case 'latin-american':
                return $query->where('ethnicity', 'latin american');
            case 'pedagogical':
                return $query->where('is_pedagogical', true);
            default:
                return $query;
        }
    }
}
