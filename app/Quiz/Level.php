<?php

namespace App\Quiz;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $table = 'quiz_levels';
    protected $guarded = [];
    protected $types = [
    		1 => 'green',
    		2 => 'yellow',
            3 => 'orange',
    		4 => 'purple',
    		5 => 'red'
    	];
   	protected $appends = ['color', 'index'];

    public function quizzes()
    {
    	return $this->hasMany(Quiz::class);
    }

    public function getColorAttribute()
    {
    	return $this->types[$this->id];
    }

    public function getIndexAttribute()
    {
    	return array_search($this->color, array_values($this->types));
    }
}
