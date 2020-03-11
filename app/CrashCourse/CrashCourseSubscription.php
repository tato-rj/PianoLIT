<?php

namespace App\CrashCourse;

use App\{PianoLit, Subscription};
use App\Mail\CrashCourseEmail;

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

	public function run()
	{
		if (!$this->started_at)
			$this->update(['started_at' => now()]);

		if ($this->upcomingLesson)
        	\Mail::to($this->subscriber->email)->queue(new CrashCourseEmail($this));

		return $this->previousLesson()->associate($this->upcomingLesson);
	}

	public function finish()
	{
		return $this->update(['completed_at' => now()]);
	}

	public function cancel()
	{
		return $this->update(['cancelled_at' => now()]);
	}

	public function getRemainingLessonsCountAttribute()
	{
		if (! $this->previousLesson)
			return $this->lessons_count;

		return $this->lessons_count - ($this->previousLessonIndex + 1);
	}
}
