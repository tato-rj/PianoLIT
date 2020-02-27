<?php

namespace App\Stats;

use App\{Piece, Composer};

class PieceStats extends StatsFactory
{
    public function __construct()
    {
        $this->table = \DB::table('pieces');
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

    public function gender()
    {
        $this->title = 'Pieces by gender';
        $this->colors = [$this->color['pink'], $this->color['blue']];
        $this->data = collect([
            [
                'label' => 'male',
                'count' => Piece::byGender('male')->count()
            ],
            [
                'label' => 'female',
                'count' => Piece::byGender('female')->count()
            ]
        ]);
        
        return $this;
    }
}
