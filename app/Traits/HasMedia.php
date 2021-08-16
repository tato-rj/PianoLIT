<?php

namespace App\Traits;

trait HasMedia
{
    public function getMediaAttribute()
    {
        $performance = $this->tutorials()->byType('performance')->first();
        $synthesia = $this->tutorials()->byType('synthesia')->first();
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

        return compact(['performance', 'synthesia', 'lessons']);
    }
}