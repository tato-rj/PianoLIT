<?php

namespace App\CrashCourse;

use App\{ShareableContent, Subscription};
use App\Events\CrashCourses\CrashCourseSignUp;

class CrashCourse extends ShareableContent
{
	protected $withCount = ['lessons', 'subscriptions'];

    public function subscriptions()
    {
    	return $this->hasMany(CrashCourseSubscription::class);
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

    public function signup(Subscription $subscription, $name)
    {
		$subscription = CrashCourseSubscription::create([
            'first_name' => $name,
			'crash_course_id' => $this->id,
			'subscriber_id' => $subscription->id
		]);

        event(new CrashCourseSignUp($subscription));

        return $subscription;
    }
}
