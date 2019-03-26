<?php

namespace App\Blog;

use App\Traits\FindBySlug;
use App\{PianoLit, Admin};
use Illuminate\Http\Request;
use App\Tools\Cropper;

class Post extends PianoLit
{
	use FindBySlug;
	
	protected $casts = ['is_published' => 'boolean'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($blog) {
            \Storage::disk('public')->delete($blog->cover_path);

            // $blog->files->each(function($file) {
            //     $file->delete();
            // });
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function updateStatus()
    {
    	$this->update(['is_published' => ! $this->is_published]);
    }

    public function cover_image()
    {
        return asset('storage/' . $this->cover_path);
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
        return $query->where('is_published', true);
    }

    public function uploadCoverImage(Request $request)
    {
        if ($request->hasFile('cover_image')) {
            if ($this->cover_path)
                \Storage::disk('public')->delete($this->cover_path);

            $this->update([
                'cover_path' => (new Cropper($request))->make('cover_image')->saveTo('blog/cover_images/')->getPath()
            ]);
        }
    }
}
