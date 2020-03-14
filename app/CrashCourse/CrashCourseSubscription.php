<?php

namespace App\CrashCourse;

use App\{PianoLit, Subscription};
use App\Events\CrashCourses\{CrashCourseCancelled, CrashCourseFinished};
use App\Mail\{CrashCourseEmail, CrashCourseFeedbackEmail};

class CrashCourseSubscription extends PianoLit
{
	protected $dates = ['started_at', 'last_sent_at', 'completed_at', 'cancelled_at'];
	protected $with = ['crashcourse'];

	public function crashcourse()
	{
		return $this->belongsTo(CrashCourse::class, 'crash_course_id');
	}

	public function subscriber()
	{
		return $this->belongsTo(Subscription::class, 'subscriber_id');
	}

	public function previousLesson()
	{
		return $this->belongsTo(CrashCourseLesson::class, 'last_sent_lesson_id');
	}

	public function scopeActive()
	{
		return $this->where([
    		['completed_at', null], 
    		['cancelled_at', null]
    	]);
	}

	public function getUpcomingLessonAttribute()
	{
		if (! $this->previousLesson)
			return $this->lessons->first();

		if ($this->previousLessonIndex + 1 == $this->lessons_count)
			return null;

		return $this->lessons->slice($this->previousLessonIndex + 1, 1)->first();
	}

	public function getLessonsAttribute()
	{
        return $this->crashcourse->lessons;
	}

	public function getLessonsCountAttribute()
	{
		return $this->crashcourse->lessons_count;
	}

	public function getPreviousLessonIndexAttribute()
	{
		return $this->lessons->search($this->previousLesson);
	}

	public function start()
	{
		$this->update(['started_at' => now()]);
		$this->send();
	}

	public function continue()
	{
		return $this->upcomingLesson ? $this->send() : $this->finish();
	}

	public function send()
	{
    	\Mail::to($this->subscriber->email)->queue(new CrashCourseEmail($this));

    	$this->update(['last_sent_at' => now()]);
    	
    	$this->previousLesson()->associate($this->upcomingLesson)->save();
	}

	public function cancel()
	{
        event(new CrashCourseCancelled($this));

		return $this->update(['cancelled_at' => now()]);
	}

	public function finish()
	{
        \Mail::to($this->subscriber->email)->queue(new CrashCourseFeedbackEmail($this->crashcourse, $this));

        event(new CrashCourseFinished($this));

		return $this->update(['completed_at' => now()]);		
	}

	public function getRemainingLessonsCountAttribute()
	{
		if (! $this->previousLesson)
			return $this->lessons_count;

		return $this->lessons_count - ($this->previousLessonIndex + 1);
	}
}
