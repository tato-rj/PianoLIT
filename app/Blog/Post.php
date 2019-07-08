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
	protected $dates = ['published_at'];

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

    public function getReferencesArrayAttribute()
    {
        return unserialize($this->references);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    public function getIsNewAttribute()
    {
        return $this->published_at->isSameMonth(now());
    }

    public function hasGift()
    {
        return ! is_null($this->gift_path);
    }

    public function isPublished()
    {
        return ! is_null($this->published_at);
    }

    public function updateStatus()
    {
        if ($this->published_at) {
        	$this->update(['published_at' => null]);
        } else {
            $this->update(['published_at' => now()]);
        }
    }

    public function getStatusAttribute()
    {
        return $this->published_at ? 'published' : 'unpublished';
    }

    public function scopeByTitle($query, $title)
    {
    	return $query->where('title', $title)->first();
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->latest();
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
