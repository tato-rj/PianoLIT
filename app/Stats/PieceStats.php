<?php

namespace App\Stats;

use App\{Piece, Composer};

class PieceStats extends StatsFactory
{
    public function __construct()
    {
        $this->table = \DB::table('pieces');
    }

    public function videos()
    {
        $this->title = 'Pieces by videos';
        $this->colors = [$this->color['pink'], $this->color['grey']];
        $this->data = collect([
            [
                'label' => 'has videos',
                'count' => Piece::where('videos', '!=', null)->orWhere('videos', '!=', 'b:0;')->count()
            ],
            [
                'label' => 'no videos',
                'count' => Piece::where('videos', null)->orWhere('videos', 'b:0;')->count()
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
