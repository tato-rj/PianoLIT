<?php

namespace App\Infograph;

use App\{ShareableContent, Admin};
use App\Contracts\Merchandise;

class Infograph extends ShareableContent implements Merchandise
{
    protected $folder = 'infograph';
    protected $report_by = 'name';

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

    public function purchases()
    {
        return $this->morphMany('App\Merchandise\Purchase', 'item');
    }

    public function related()
    {
        $related = collect();

        foreach ($this->topics as $topic) {
            $related->push($topic->infographs()->where('id', '!=', $this->id)->published()->get());
        }

        return $related->flatten()->unique()->shuffle()->take(8);
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

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'published' => view('admin.pages.infographs.toggles.published'),
            'gift' => view('admin.pages.infographs.toggles.gift'),
            'action' => view('admin.pages.infographs.actions')
        ])->make();
    }
}
