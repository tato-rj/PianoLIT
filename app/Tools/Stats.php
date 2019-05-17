<?php

namespace App\Tools;

use App\Piece;

class Stats
{
	protected $milestones = [100, 500, 1000, 2000, 5000, 10000];

    public function progress($count)
    {
        return \DB::table('pieces')
                      ->selectRaw('month(created_at) month, day(created_at) day, year(created_at) year, count(*) count')
                      ->groupBy('month', 'day', 'year')
                      ->orderByRaw('min(created_at)')
                      ->get()
                      ->slice(-$count)
                      ->values();
    }

    public function average($days)
    {
        $piecesAdded = Piece::whereBetween('created_at', [now()->copy()->subDays($days), now()])->count();

        $avg = $piecesAdded / $days;

        return ($avg > 0 && $avg < 1) ? 'Less than 1' : round($avg);
    }

    public function milestone($avg)
    {
    	$pieces_count = Piece::count();
    	$report = [];
dd($avg);
    	foreach ($this->milestones as $milestone) {
			if ($pieces_count < $milestone) {
				$report['current'] = $pieces_count;
				$report['goal'] = $milestone;

				if (is_integer($avg) && $avg > 0)
					$report['days_left'] = round(($milestone - $pieces_count) / $avg);

				break;
			}
    	}

    	return $report;
    }
}
