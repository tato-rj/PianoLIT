<?php

namespace App\CrashCourse;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};

class CrashCourseTopic extends PianoLit
{
	use FindBySlug;

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($topic) {
            $topic->crashcourses()->detach();
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function crashcourses()
    {
        return $this->belongsToMany(CrashCourse::class, 'crash_course_crash_course_topic', 'crash_course_id', 'topic_id');
    }
}
