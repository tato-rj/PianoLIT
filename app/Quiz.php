<?php

namespace App;

class Quiz extends PianoLit
{
    public function getQuestionsAttribute($questions)
    {
    	return array_values(unserialize($questions));
    }

    public function getAnswer($key)
    {
    	if (! array_key_exists($key, $this->questions))
    		abort(422, 'This question does not exist');

    	$result;

    	foreach ($this->questions[$key]['A'] as $index => $answer) {
    		if (strhas($answer, '[x]'))
    			$result = $index;
    	}

    	return $result;
    }

    public function evaluate(array $answers)
    {
    	$outcome = [];
    	$score = 0;

    	foreach ($answers as $index => $answer) {
    		$result = $answer == $this->getAnswer($index) ? true : $answer;

    		if ($result === true)
    			$score += 1;

    		array_push($outcome, $result);
    	}

    	return [
    		'score' => $score,
    		'results' => $outcome
    	];
    }
}
