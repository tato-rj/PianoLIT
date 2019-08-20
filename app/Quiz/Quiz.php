<?php

namespace App\Quiz;

use App\{ShareableContent, Admin};
use App\Traits\FindBySlug;

class Quiz extends ShareableContent
{
    protected $folder = 'quiz';

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($quiz) {
            \Storage::disk('public')->delete($quiz->cover_path);
        });
    }

    public function results()
    {
        return $this->hasMany(QuizResult::class);    
    }

    public function getQuestionsAttribute($questions)
    {
        return array_values(unserialize($questions));        
    }

    public function questions()
    {
        $array = $this->questions;

        foreach ($array as $index => $question) {
            $array[$index]['Q'] = preg_replace("/\[[^)]+\]/", '',$question['Q']);
            $array[$index]['audio'] = $this->getAudio($question['Q']);
        }

        return $array;
    }

    public function getDurationAttribute()
    {
        $count = count($this->questions);
        $seconds = 8 * $count;
        $duration = intval(ceil($seconds/60));

        return $duration . str_plural('minute', $duration);
    }

    public function getFeedbackAttribute($feedback)
    {
        return array_values(unserialize($feedback));
    }

    public function getFeedback($score)
    {
        $percentage = percentage($score, count($this->questions));
        $slots = [24, 49, 74, 99, 100];
        $feedback;

        foreach ($slots as $index => $slot) {
            if ($percentage <= $slot) {
                $feedback = $this->feedback[$index];
                break;  
            }
        }

        return $feedback ?? 'Thanks for taking our quiz!';
    }

    public function getAudio($question)
    {
        preg_match('#\[(.*?)\]#', $question, $url);

        return array_key_exists(1, $url) ? $url[1] : null;
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

    public function getAverageAttribute()
    {
        $average = $this->results->avg('score');

        return percentage($average, count($this->questions));
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
    		'results' => $outcome,
            'feedback' => $this->getFeedback($score)
    	];
    }
}
