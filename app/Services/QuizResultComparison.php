<?php

namespace App\Services;

use App\Quiz\Quiz;

class QuizResultComparison
{
    public function beforeCurrentPlay(Quiz $quiz, int $score, int $total): array
    {
        // Keep the benchmark independent of this answer submission. Do not load players.
        $history = $quiz->results()->selectRaw('COUNT(*) AS plays, AVG(score) AS average, MIN(score) AS lowest, MAX(score) AS highest')->first();
        $plays = (int) $history->plays;
        // Old results have no question-count/version snapshot. Never squeeze an
        // incompatible historical score into today's scale or invent a zero average.
        $available = $total > 0 && $plays > 0 && $history->lowest >= 0 && $history->highest <= $total;
        $average = $available ? round((float) $history->average, 1) : null;
        $standing = !$plays ? 'first' : (!$available ? 'unavailable' : ($score > $average ? 'above' : ($score < $average ? 'below' : 'tied')));

        return [
            'plays' => $plays, 'average' => $average, 'standing' => $standing,
            'average_label' => $available ? rtrim(rtrim(number_format($average, 1, '.', ''), '0'), '.') : null,
            'average_percent' => $available ? round($average / $total * 100, 1) : null,
            'score_percent' => $total > 0 ? max(0, min(100, round($score / $total * 100, 1))) : 0,
        ];
    }
}
