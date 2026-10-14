<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FeedbackSubmission extends Model
{
    const RATINGS = ['bad' => 'Bad', 'not_bad' => 'Not bad', 'good' => 'Good'];

    protected $fillable = ['user_id', 'rating', 'message'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
