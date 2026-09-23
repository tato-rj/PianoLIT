<?php

namespace App\Traits;

trait HasMedia
{
    public function getMediaAttribute()
    {
        if ($this->relationLoaded('tutorials')) {
            $byType = function ($type) {
                return $this->tutorials->filter(function ($tutorial) use ($type) {
                    return stripos($tutorial->type, $type) !== false;
                })->values();
            };
            $performance = $byType('performance')->first();
            $synthesia = $byType('synthesia')->first();
            $lessons = collect([
                ['title' => 'Harmony', 'videos' => $byType('harmonic analysis')],
                ['title' => 'Practicing tips', 'videos' => $byType('tutorial')],
                ['title' => 'Slow performance', 'videos' => $byType('slow')],
            ])->filter(function ($lesson) { return $lesson['videos']->isNotEmpty(); })->values()->all();

            return compact('performance', 'synthesia', 'lessons');
        }

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

        $lessons = array_values($lessons);

        return compact(['performance', 'synthesia', 'lessons']);
    }
}