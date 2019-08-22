<?php

namespace App\Quiz;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FindBySlug;
use App\Admin;

class Topic extends Model
{
	use FindBySlug;
	
    protected $table = 'quiz_topics';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($topic) {
            $topic->quizzes()->detach();
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_quiz_topic');
    }

    public function scopeExclude($query, $exclude)
    {
        return $query->whereNotIn('id', $exclude);
    }
}
