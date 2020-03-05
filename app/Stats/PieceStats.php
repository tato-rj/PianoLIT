<?php

namespace App\Stats;

use App\{Piece, Composer, Tag};

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

    public function level($where = null)
    {
        $this->where($where);

        $this->title = 'Pieces by level';
        $this->colors = [
            $this->getColor('yellow'), 
            $this->getColor('pink'), 
            $this->getColor('green'), 
            $this->getColor('blue'), 
            $this->getColor('orange'), 
            $this->getColor('purple')
        ];
        $this->data = collect([
            [
                'label' => 'elementary',
                'count' => Tag::name('elementary')->withCount('pieces')->first()->pieces_count
            ],
            [
                'label' => 'early beginner',
                'count' => Tag::name('early beginner')->withCount('pieces')->first()->pieces_count
            ],
            [
                'label' => 'late beginner',
                'count' => Tag::name('late beginner')->withCount('pieces')->first()->pieces_count
            ],
            [
                'label' => 'early intermediate',
                'count' => Tag::name('early intermediate')->withCount('pieces')->first()->pieces_count
            ],
            [
                'label' => 'late intermediate',
                'count' => Tag::name('late intermediate')->withCount('pieces')->first()->pieces_count
            ],
            [
                'label' => 'advanced',
                'count' => Tag::name('advanced')->withCount('pieces')->first()->pieces_count
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

    public function ethnicity()
    {
        $this->title = 'Pieces by ethnicity';
        $this->colors = [$this->color['cyan'], $this->color['purple'], $this->color['orange'], $this->color['pink']];
        $this->data = collect();

        foreach (ethnicities() as $ethnicity) {
            $this->data->push([
                'label' => $ethnicity,
                'count' => Piece::byEthnicity($ethnicity)->count()
            ]);
        }
        
        return $this;
    }
}
