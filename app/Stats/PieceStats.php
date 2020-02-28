<?php

namespace App\Stats;

use App\{Piece, Composer};

class PieceStats extends StatsFactory
{
    public function __construct()
    {
        $this->table = \DB::table('pieces');
    }

    public function period()
    {
        $this->title = 'Pieces by period';
        $this->colors = [$this->color['cyan'], $this->color['pink'], $this->color['red'], $this->color['orange'], $this->color['blue'], $this->color['green'], $this->color['purple']];
        $this->data = collect([
            [
                'label' => 'baroque',
                'count' => Piece::byPeriod('baroque')->count()
            ],
            [
                'label' => 'classical',
                'count' => Piece::byPeriod('classical')->count()
            ],
            [
                'label' => 'romantic',
                'count' => Piece::byPeriod('romantic')->count()
            ],
            [
                'label' => 'impressionist',
                'count' => Piece::byPeriod('impressionist')->count()
            ],
            [
                'label' => 'modern',
                'count' => Piece::byPeriod('modern')->count()
            ],
            [
                'label' => 'jazz',
                'count' => Piece::byPeriod('jazz')->count()
            ],
            [
                'label' => 'contemporary',
                'count' => Piece::byPeriod('contemporary')->count()
            ]
        ]);
        
        return $this;   
    }

    public function level()
    {
        $this->title = 'Pieces by level';
        $this->colors = [$this->color['green'], $this->color['blue'], $this->color['orange'], $this->color['purple']];
        $this->data = collect([
            [
                'label' => 'elementary',
                'count' => Piece::byLevel('elementary')->count()
            ],
            [
                'label' => 'beginner',
                'count' => Piece::byLevel('beginner')->count()
            ],
            [
                'label' => 'intermediate',
                'count' => Piece::byLevel('intermediate')->count()
            ],
            [
                'label' => 'advanced',
                'count' => Piece::byLevel('advanced')->count()
            ]
        ]);
        
        return $this;   
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
