<?php

namespace App\Blog;

use App\{ShareableContent, Admin};
use App\Traits\Filterable;

class Post extends ShareableContent
{
    use Filterable;

    protected $searchableColumns = ['title', 'content'];
    protected $folder = 'blog';
    protected $with = ['topics'];
    protected $appends = ['cover_image', 'app_url'];
    protected $report_by = 'title';
    public $route = 'posts.index';

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($post) {
            $post->topics()->detach();
            
            \Storage::disk('public')->delete($post->cover_path);
            \Storage::disk('public')->delete($post->thumbnail_path);
        });
    }

    public function getReferencesArrayAttribute()
    {
        return unserialize($this->references);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    public function hasGift()
    {
        return ! is_null($this->gift_path);
    }

    public function getAppUrlAttribute()
    {
        return route('posts.app', $this);
    }

    public function getCoverImageAttribute()
    {
        return $this->cover_image();
    }

    public function scopeByTopic($query, Topic $topic)
    {
        return $query->whereHas('topics', function($q) use ($topic) {
            $q->where('slug', $topic->slug);
        });
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'title' => view('admin.pages.blog.table.title'),
            'reading_time' => view('admin.pages.blog.table.duration'),
            'published' => view('admin.pages.blog.table.published'),
            'action' => view('admin.pages.blog.table.actions')
        ])->make();
    }
}
