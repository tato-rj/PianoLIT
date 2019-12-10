<?php

namespace App\Infograph;

use App\{ShareableContent, Admin};

class Infograph extends ShareableContent
{
    protected $folder = 'infograph';
    protected $report_by = 'name';
    // protected $types = ['composers' => 0, 'theory' => 0, 'curiosity' => 0, 'quotes' => 0, 'piano' => 0, 'history' => 0];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($infograph) {
            $infograph->topics()->detach();
            \Storage::disk('public')->delete([$infograph->cover_path, $infograph->thumbnail_path]);
        });
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'infograph_infograph_topic');
    }

    // public function scopeTypes($query)
    // {
    //     $records = $query->published()->selectRaw('type, count(*) count')->groupBy('type')->get()->toArray();

    //     foreach ($this->types as $type => $count) {
    //         foreach ($records as $record) {
    //             if ($type == $record['type'])
    //                 $this->types[$type] = $record['count'];
    //         }
                
    //     }

    // 	ksort($this->types);

    //     return $this->types;
    // }
    
    public function related()
    {
        $related = collect();

        foreach ($this->topics as $topic) {
            $related->push($topic->infographs()->where('id', '!=', $this->id)->published()->get());
        }

        return $related->flatten()->unique();
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
