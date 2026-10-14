<?php

namespace App\Analytics\Factories;

use App\Log;
use App\Activity\DailyAnalytics;

class LogAnalytics extends Factory
{
    public function __construct()
    {
        $this->table = \App\Activity\ExcludedAccounts::users(\DB::table('users'));
    }

    public function daily($where = null)
    {
        $this->title = 'Activity logs';
        $this->colors = [$this->color['cyan'], $this->color['orange'], $this->color['purple']];    
        $this->data = (new DailyAnalytics)->latest($where['logs_limit'] ?? 6);

        return $this;
    }

    public function range($where = null)
    {
        $totals = \App\Activity\ExcludedAccounts::events(\DB::table('activity_events'))->whereNotNull('user_id')->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->get();
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
