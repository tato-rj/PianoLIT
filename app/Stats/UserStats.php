<?php

namespace App\Stats;

use App\User;

class UserStats extends StatsFactory
{
    public function __construct()
    {
        $this->table = \DB::table('users');
    }

    public function get()
    {
        return [
            'title' => $this->title, 
            'labels' => $this->data->pluck('label'), 
            'records' => $this->data->pluck('count'),
            'colors' => $this->colors
        ];
    }

    public function where($conditions = [])
    {
        foreach ($conditions as $key => $condition) {
            if ($condition)
                $this->table = $this->table->where($key, $condition);        
        }

        return $this;
    }

    public function daily($where = null)
    {
        $this->where($where);

        $this->title = 'New sign ups';
        $this->colors = [$this->color['blue']];
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M %D") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
    }

    public function monthly($where = null)
    {
        $this->where($where);

        $this->title = 'New sign ups';
        $this->colors = [$this->color['green']];
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%M") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
    }

    public function yearly($where = null)
    {
        $this->where($where);

        $this->title = 'New sign ups';
        $this->colors = [$this->color['orange']];
        $this->data = $this->table->selectRaw('DATE_FORMAT(created_at, "%Y") as label, count(*) as count')
                    ->groupBy('label')
                    ->orderByRaw('min(created_at)')
                    ->get();
        return $this;
    }

    public function gender($where = null)
    {
        $this->where($where);

        $this->title = 'Users by gender';
        $this->colors = [$this->color['pink'], $this->color['blue']];
        $this->data = $this->table->whereNotNull('gender')->selectRaw('gender as label, count(*) count')
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
        return $this;
    }

    public function confirmed($where = null)
    {
        $this->where($where);

        $this->title = 'Email confirmed';
        $this->colors = [$this->color['cyan'], $this->color['grey']];
        $total = $this->table->count();
        $verified = $this->table->whereNotNull('email_verified_at')->count();

        $this->data = collect([
            [
                'label' => 'verified',
                'count' => $verified
            ],
            [
                'label' => 'not verified',
                'count' => $total - $verified
            ]
        ]);

        return $this;
    }

    public function favorites()
    {
        $this->title = 'Favorites';
        $this->colors = [$this->color['red'], $this->color['grey']];
        $total = User::where('origin', 'ios')->count();
        $hasFavs = User::where('origin', 'ios')->has('favorites')->count();

        $this->data = collect([
            [
                'label' => 'with favorites',
                'count' => $hasFavs
            ],
            [
                'label' => 'no favorites',
                'count' => $total - $hasFavs
            ]
        ]);

        return $this;
    }
}
