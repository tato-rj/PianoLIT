<?php

namespace App\CrashCourse;

use App\{ShareableContent, Subscription};
use App\Events\CrashCourses\CrashCourseSignUp;

class CrashCourse extends ShareableContent
{
	protected $withCount = ['lessons', 'subscriptions'];
    
    protected static function boot()
    {
        parent::boot();

        self::deleting(function($crashcourse) {
            $crashcourse->topics()->detach();
            \Storage::disk('public')->delete([$crashcourse->cover_path, $crashcourse->thumbnail_path]);
        });
    }

    public function subscriptions()
    {
    	return $this->hasMany(CrashCourseSubscription::class);
    }

    public function topics()
    {
        return $this->belongsToMany(CrashCourseTopic::class, 'crash_course_crash_course_topic', 'crash_course_id', 'topic_id');
    }

    public function activeSubscriptions()
    {
    	return $this->subscriptions()->where([
    		['completed_at', null], 
    		['cancelled_at', null]
    	]);
    }

    public function cancelledSubscriptions()
    {
    	return $this->subscriptions()->whereNotNull('cancelled_at');
    }

    public function completedSubscriptions()
    {
    	return $this->subscriptions()->whereNotNull('completed_at');
    }

    public function lessons()
    {
    	return $this->hasMany(CrashCourseLesson::class, 'crash_course_id');
    }

    public function signup(Subscription $subscription, $name = null)
    {
		$subscription = CrashCourseSubscription::create([
            'first_name' => $name,
			'crash_course_id' => $this->id,
			'subscriber_id' => $subscription->id
		]);

        event(new CrashCourseSignUp($subscription));

        return $subscription;
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withCount(['lessons'])->withBlade([
            'published' => view('admin.pages.crashcourses.table.published'),
            'action' => view('admin.pages.crashcourses.table.actions')
        ])->make();
    }
}
