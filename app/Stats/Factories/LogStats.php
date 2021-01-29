<?php

namespace App\Stats\Factories;

use App\Log;
use App\Log\Loggers\DailyLog;

class LogStats extends Factory
{
    public function __construct()
    {
        $this->table = \DB::table('users');
    }

    public function daily($where = null)
    {
        $this->title = 'Activity logs';
        $this->colors = [$this->color['cyan'], $this->color['orange'], $this->color['purple']];    
        $this->data = (new DailyLog)->latest($where['logs_limit'] ?? 6);

        return $this;
    }

    public function range($where = null)
    {
        $totals = Log::orderBy('total')->get('total');
        $ranges = [[1,2], [3,5], [6,12], [13,30], [31]];

        $this->title = 'Logs by range';
        $this->colors = [$this->color['grey'], $this->color['yellow'], $this->color['green'], $this->color['blue'], $this->color['purple']];    
        $this->data = collect();

        foreach($ranges as $range) {
            $this->data->push(collect([
                'label' => count($range) > 1 ? implode('-', $range) : $range[0].'+',
                'count' => count($range) > 1 ? $totals->whereBetween('total', $range)->count() : $totals->where('total', '>', $range[0])->count()
            ]));
        }

        return $this;
    }
}
