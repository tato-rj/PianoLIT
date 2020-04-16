<?php

namespace App\CrashCourse;

use App\{ShareableContent, Subscription};
use App\Events\CrashCourses\CrashCourseSignUp;

class CrashCourse extends ShareableContent
{
    protected $searchableColumns = ['title', 'description'];
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

    public function hasActive($email)
    {
        return $this->subscriptions()->where([
            ['completed_at', null], 
            ['cancelled_at', null]
        ])->whereHas('subscriber', function($query) use ($email) {
            $query->where('email', $email);
        })->exists();
    }

    public function getActiveSubscriptionsAttribute()
    {
    	return $this->subscriptions()->where([
    		['completed_at', null], 
    		['cancelled_at', null]
    	])->get();
    }

    public function getCancelledSubscriptionsAttribute()
    {
    	return $this->subscriptions()->whereNotNull('cancelled_at')->get();
    }

    public function getCompletedSubscriptionsAttribute()
    {
    	return $this->subscriptions()->whereNotNull('completed_at')->get();
    }

    public function lessons()
    {
    	return $this->hasMany(CrashCourseLesson::class, 'crash_course_id')->orderBy('order');
    }

    public function signup(Subscription $subscription, $name = null)
    {
		$subscription = CrashCourseSubscription::create([
            'first_name' => $name,
			'crash_course_id' => $this->id,
			'subscriber_id' => $subscription->id,
            'crash_course_title' => $this->title,
            'email' => $subscription->email
		]);

        event(new CrashCourseSignUp($subscription));

        return $subscription;
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withCount(['lessons', 'active_subscriptions'])->withBlade([
            'published' => view('admin.pages.crashcourses.table.published'),
            'action' => view('admin.pages.crashcourses.table.actions')
        ])->make();
    }
}
