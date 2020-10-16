<?php

namespace App;

use App\Traits\FindBySlug;
use Illuminate\Http\Request;
use App\Tools\Cropper;

abstract class ShareableContent extends PianoLit
{
	use FindBySlug;	

    protected $searchableColumns = ['title'];
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
        return $this->published_at->gte(now()->subWeek());
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', $title)->first();
    }

    public function updateStatus($attribute = null)
    {
        $attribute = $attribute ?? 'published_at';

        if ($this->$attribute) {
        	$this->update([$attribute => null]);
        } else {
            $this->update([$attribute => now()]);
        }
    }

    public function getStatusAttribute()
    {
        return $this->published_at ? 'published' : 'unpublished';
    }

    // public function uploadCoverImage(Request $request, $crop = true, $filename = 'cover_image')
    // {
    //     $pathname = str_replace('_image', '_path', $filename);

    //     if ($request->hasFile($filename)) {
    //         if ($this->$pathname)
    //             \Storage::disk('public')->delete([$this->$pathname, $this->thumbnail_path]);

    //         $subfolder = str_plural($filename);
            
    //         $this->update([
    //             $pathname => (new Cropper($request, $crop))->withThumbnail()->make($filename)->saveTo("$this->folder/$subfolder/$this->id/")->getPath()
    //         ]);
    //     }
    // }

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

    public function scopeNew($query)
    {
        return $query->whereBetween('published_at', [now()->copy()->subWeek(), now()]);
    }

    public function scopeExceptNew($query)
    {
        return $query->whereNotBetween('published_at', [now()->copy()->subWeek(), now()]);
    }

    public function scopeSuggestions($query, $number = 4)
    {
        return $query->except('id', [$this->id])->inRandomOrder()->published()->take($number);
    }

    public function scopeSearch($query, $input)
    {
        $columns = $this->searchableColumns;

        return $query->where(function($q) use ($columns, $input) {
            $q->where(array_shift($columns), 'LIKE', '%'.$input.'%');
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', '%'.$input.'%');
            }
        });
    }
}
