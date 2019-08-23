<?php

namespace App\Quiz;

use App\{ShareableContent, Admin};
use App\Traits\FindBySlug;
use App\Quiz\Traits\Feedback;

class Quiz extends ShareableContent
{
    use Feedback;

    protected $folder = 'quiz';
    protected $withCount = ['results'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($quiz) {
            $quiz->topics()->detach();
            \Storage::disk('public')->delete($quiz->cover_path);
        });
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'quiz_quiz_topic');
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function results()
    {
        return $this->hasMany(QuizResult::class);    
    }

    public function scopeByTopic($query, Topic $topic)
    {
        return $query->whereHas('topics', function($q) use ($topic) {
            $q->where('slug', $topic->slug);
        });
    }

    public function getQuestionsAttribute($questions)
    {
        return array_values(unserialize($questions));        
    }

    public function getAverageScoreAttribute()
    {
        return intval(round($this->results()->avg('score')));
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

        return $duration . ' ' . str_plural('minute', $duration);
    }

    public function getFeedback($score)
    {
        $percentage = percentage($score, count($this->questions));
        $slots = [24, 49, 74, 99, 100];
        $feedback;

        foreach ($slots as $index => $slot) {
            if ($percentage <= $slot) {
                $gif = $this->gif($index);
                $feedback = $this->feedbacks[$index];
                break;  
            }
        }

        return ['gif' => $gif, 'sentence' => $feedback];
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
        $count = count($this->questions);

    	foreach ($answers as $index => $answer) {
    		$result = $answer == $this->getAnswer($index) ? true : $answer;

    		if ($result === true)
    			$score += 1;

    		array_push($outcome, $result);
    	}

        $percentage = percentage($score, $count);

        $feedback = $this->getFeedback($score);

    	return [
            'total' => $count,
    		'score' => $score,
            'percentage' => $percentage,
    		'results' => $outcome,
            'feedback' => $feedback['sentence'],
            'gif' => $feedback['gif']
    	];
    }
}
