<?php

namespace App\Games\TrueOrFalse;

use App\Games\TrueOrFalse\Statements;
use App\Games\Traits\Feedback;

class TrueOrFalse
{
	use Statements, Feedback;

	public function statements(array $levels)
	{
		$game = [];

		foreach ($levels as $level) {
			if (! property_exists($this, $level))
				abort(422, 'The level '.$level.' does not exist');

			$game[$level] = array_slice(shuffle_assoc($this->$level), 0, 12);
		}

		return $game;
	}

    public function evaluate($score, $count)
    {
        $percentage = percentage($score, $count);

        $feedback = $this->getFeedback($score, $count);

    	return [
            'questions_count' => $count,
    		'score' => $score,
            'percentage' => $percentage,
            'feedback' => $feedback['sentence'],
            'gif' => $feedback['gif']
    	];
    }
}
