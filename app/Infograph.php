<?php

namespace App;

class Infograph extends ShareableContent
{
    protected $folder = 'infograph';
    protected $report_by = 'name';

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($infograph) {
            \Storage::disk('public')->delete([$infograph->cover_path, $infograph->thumbnail_path]);
        });
    }

    public function scopeTypes($query)
    {
    	return $query->select('type')->groupBy('type')->get()->pluck('type');
    }

    public function updateScore(bool $liked)
    {
        $action = $liked ? 'increment' : 'decrement';
        
        return $this->$action('score');
    }
}
