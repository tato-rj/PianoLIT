<?php

namespace App\Traits;

trait HasMedia
{
    public function getMediaAttribute()
    {
        $performance = $this->tutorials()->byType('performance')->first();
        $synthesia = $this->tutorials()->byType('synthesia')->first();
        // $synthesia->background_url = asset('images/webapp/synthesia-thumbnail.jpg');
        
        $tutorials = $this->tutorials()->byType('tutorial')->get();
        $harmony = $this->tutorials()->byType('harmonic analysis')->get();
        $slow = $this->tutorials()->byType('slow')->get();

        $lessons = [
        	['title' => 'Harmony', 'videos' => $harmony], 
        	['title' => 'Practicing tips', 'videos' => $tutorials], 
        	['title' => 'Slow performance', 'videos' => $slow]
        ];

        foreach ($lessons as $index => $lesson) {
        	if ($lesson['videos']->isEmpty())
        		unset($lessons[$index]);
        }

        $lessons = $lessons->values();

        return compact(['performance', 'synthesia', 'lessons']);
    }
}