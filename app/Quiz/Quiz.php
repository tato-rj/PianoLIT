<?php

namespace App\Quiz;

use App\{ShareableContent, Admin};
use App\Traits\{FindBySlug, Filterable};
use App\Games\Traits\Feedback;

class Quiz extends ShareableContent
{
    use Feedback, Filterable;

    protected $folder = 'quiz';
    protected $withCount = ['results'];
    protected $appends = ['questions'];
    protected $report_by = 'title';

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
            $array[$index]['audio'] = $this->getFile($question['Q'], 'audio');
            $array[$index]['image'] = $this->getFile($question['Q'], 'images');
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

    public function getFile($question, $type)
    {
        preg_match('#\[(.*?)\]#', $question, $url);

        if (! array_key_exists(1, $url))
            return null;

        return strhas($url[1], $type) ? $url[1] : null;
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

        $feedback = $this->getFeedback($score, count($this->questions));

    	return [
            'questions_count' => $count,
    		'score' => $score,
            'quiz_average' => $this->average_score,
            'percentage' => $percentage,
    		'results' => $outcome,
            'feedback' => $feedback['sentence'],
            'gif' => $feedback['gif']
    	];
    }
}
