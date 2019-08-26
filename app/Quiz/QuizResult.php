<?php

namespace App\Quiz;

use App\PianoLit;

class QuizResult extends PianoLit
{
    public function scopeStats($query, $count)
    {
    	return \DB::table('quiz_results')->selectRaw('month(created_at) month, day(created_at) day, year(created_at) year, count(*) count')
                     ->groupBy('month', 'day', 'year')
                     ->orderByRaw('min(created_at)')
                     ->get()
                     ->slice(-$count)
                     ->values();
    }
}
