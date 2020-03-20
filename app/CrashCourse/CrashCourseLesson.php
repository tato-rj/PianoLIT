<?php

namespace App\CrashCourse;

use App\PianoLit;

class CrashCourseLesson extends PianoLit
{
	public function dynamic($attribute, $subscription = null)
	{
        if (! strhas($this->$attribute, '['))
            return $this->$attribute;
        
        preg_match_all("/\[([^\]]*)\]/", $this->$attribute, $matches);
        
        $placeholder = $matches[0][0];
        
        $key = $matches[1][0];

        $value = $subscription ? $subscription->$key : 'Joe';

        return str_replace($placeholder, $value, $this->$attribute);
	}

    public function cancelUrl($subscription = null)
    {
        return $subscription ? route('crashcourses.cancel', ['email' => $subscription->email]) : null;   
    }
}
