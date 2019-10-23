<?php

namespace App;

use App\Traits\FindBySlug;
use Illuminate\Http\Request;
use App\Tools\Cropper;

abstract class ShareableContent extends PianoLit
{
	use FindBySlug;	

    protected $thumbnailFolder = 'thumbnails';
	protected $dates = ['published_at'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function isPublished()
    {
        return ! is_null($this->published_at);
    }

    public function getIsNewAttribute()
    {
        return $this->published_at->isSameMonth(now());
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', $title)->first();
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

    public function uploadCoverImage(Request $request)
    {
        if ($request->hasFile('cover_image')) {
            if ($this->cover_path)
                \Storage::disk('public')->delete([$this->cover_path, $this->thumbnail_path]);

            dd('Not reaching this, but it should!');
            
            $this->update([
                'cover_path' => (new Cropper($request))->withThumbnail()->make('cover_image')->saveTo($this->folder . '/cover_images/')->getPath()
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

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeSuggestions($query, $number)
    {
        return $query->inRandomOrder()->published()->take(4);
    }
}
