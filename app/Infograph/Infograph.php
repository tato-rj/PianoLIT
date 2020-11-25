<?php

namespace App\Infograph;

use App\Admin;
use App\Merchandise\Product;
use App\Shop\Contract\Merchandise;
use App\Traits\Filterable;

class Infograph extends Product implements Merchandise
{
    use Filterable;

    protected $searchableColumns = ['name', 'description'];
    protected $folder = 'infograph';
    protected $report_by = 'name';
    protected $price = 0;
    public $route = 'resources.infographs.index';

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

    public function showRoute()
    {
        return route('resources.infographs.show', $this);
    }

    public function indexRoute()
    {
        return route('resources.infographs.index');
    }

    public function checkoutRoute()
    {
        return null;
    }

    public function keywords()
    {
        return null;
    }

    public function autoDownload()
    {
        return route('infographs.download', ['infograph' => $this, 'type' => 'download']);
    }

    public function notification()
    {
        return [
            'title' => 'Infograph download',
            'message' => 'New download for the <strong>' . $this->name . '</strong> infographic.',
            'url' => route('admin.stats.infographs')
        ];
    }

    public function getTitleAttribute()
    {
        return $this->name;
    }

    public function url()
    {
        return route('resources.infographs.show', $this);
    }

    public function links()
    {
        $extension = extension($this->cover_path) ?? 'download';

        return [strtoupper($extension) => encrypt($this->cover_path)];
    }

    public function related()
    {
        $related = collect();

        foreach ($this->topics as $topic) {
            $related->push($topic->infographs()->whereNotIn('id', [$this->id, 53])->published()->get());
        }

        return $related->flatten()->unique('id')->shuffle()->take(8);
    }

    public function scopeGifts($query)
    {
        return $query->published()->whereNotNull('giftable_at');
    }

    public function scopeByTopic($query, Topic $topic)
    {
        return $query->whereHas('topics', function($q) use ($topic) {
            $q->where('slug', $topic->slug);
        });
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

    public function getPriceInCentsAttribute()
    {
        return null;
    }

    public function finalPrice()
    {
        return null;
    }

    public function isFree()
    {
        return true;
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'published' => view('admin.pages.infographics.toggles.published'),
            'gift' => view('admin.pages.infographics.toggles.gift'),
            'action' => view('admin.pages.infographics.actions')
        ])->make();
    }

    // public function scopeSearch($query, $input)
    // {
    //     return $query->where('name', 'LIKE', "%$input%")
    //                  ->orWhere('descrpition', 'LIKE', "%$input%")
    //                  ->latest();
    // }
}
