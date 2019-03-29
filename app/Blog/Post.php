<?php

namespace App\Blog;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};
use Illuminate\Http\Request;
use App\Tools\Cropper;

class Post extends PianoLit
{
	use FindBySlug;
	
    protected $thumbnailFolder = 'thumbnails';
	protected $casts = ['is_published' => 'boolean'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($post) {
            $post->topics()->detach();
            \Storage::disk('public')->delete($post->cover_path);

            // $blog->files->each(function($file) {
            //     $file->delete();
            // });
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    public function updateStatus()
    {
    	$this->update(['is_published' => ! $this->is_published]);
    }

    public function getStatusAttribute()
    {
        return $this->is_published ? 'published' : 'unpublished';
    }

    public function scopeByTitle($query, $title)
    {
    	return $query->where('title', $title)->first();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->latest();
    }

    public function scopeExclude($query, $ids)
    {
        return $query->whereNotIn('id', $ids);
    }

    public function scopeSuggestions($query, $number)
    {
        return $query->published()->take(4);
    }

    public function scopeByTopic($query, Topic $topic)
    {
        return $query->whereHas('topics', function($q) use ($topic) {
            $q->where('slug', $topic->slug);
        });
    }

    public function uploadCoverImage(Request $request)
    {
        if ($request->hasFile('cover_image')) {
            if ($this->cover_path)
                \Storage::disk('public')->delete([$this->cover_path, $this->thumbnail_path]);

            $this->update([
                'cover_path' => (new Cropper($request))->withThumbnail()->make('cover_image')->saveTo('blog/cover_images/')->getPath()
            ]);
        }
    }

    public function cover_image()
    {
        return asset('storage/' . $this->cover_path);
    }

    public function getThumbnailPathAttribute()
    {
        return dirname($this->cover_path) . '/thumbnails/' . basename($this->cover_path);
    }

    public function thumbnail_image()
    {
        return asset('storage/' . $this->thumbnail_path);
    }
}
