<?php

namespace App;

class Infograph extends ShareableContent
{
    protected $folder = 'infograph';
    protected $report_by = 'name';
    protected $types = ['composers' => 0, 'theory' => 0, 'curiosity' => 0, 'quotes' => 0, 'piano' => 0, 'history' => 0];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($infograph) {
            \Storage::disk('public')->delete([$infograph->cover_path, $infograph->thumbnail_path]);
        });
    }

    public function scopeTypes($query)
    {
        $records = $query->published()->selectRaw('type, count(*) count')->groupBy('type')->get()->toArray();

        foreach ($this->types as $type => $count) {
            foreach ($records as $record) {
                if ($type == $record['type'])
                    $this->types[$type] = $record['count'];
            }
                
        }

    	ksort($this->types);

        return $this->types;
    }

    public function scopeGifts($query)
    {
        return $query->published()->whereNotNull('giftable_at');
    }

    public function scopeNewFirst($query)
    {
        $infographs = $query->get();

        foreach ($infographs as $key => $infograph) {
            if ($infograph->is_new) {
                $new = $infograph;
                $infographs->forget($key);
                $infographs->prepend($infograph);
            }
        }

        return $infographs;
    }

    public function updateScore(bool $liked)
    {
        $action = $liked ? 'increment' : 'decrement';
        
        return $this->$action('score');
    }
}
