<?php

namespace App\Shop;

use App\Traits\FindBySlug;
use App\{ShareableContent, Admin};
use App\Merchandise\Purchase;
use App\Shop\Contract\Merchandise;
use Illuminate\Http\UploadedFile;

class eBook extends ShareableContent implements Merchandise
{
	use FindBySlug;

    public function topics()
    {
        return $this->belongsToMany(eBookTopic::class);
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'item');
    }

    public function similar()
    {
        return [1,2,3,4];
    }

    public function getPriceInCentsAttribute()
    {
        return $this->price * 100;
    }

    public function getPriceToHumansAttribute()
    {
        return '$' . $this->price;
    }

    public function getPreviewsAttribute($previews)
    {
        return $previews ? unserialize($previews) : [];
    }

    public function getPreviewsCountAttribute()
    {
        return count($this->previews);
    }

    public function savePreview(UploadedFile $file)
    {
        $folder = "ebooks/{$this->id}";

        $filename = 'preview-' . ($this->previews_count + 1) . '.' . $file->getClientOriginalExtension();

        $newPreview = $file->storeAs($folder, $filename, 'public');

        $previews = $this->previews;
        
        array_push($previews, $newPreview);

        return $this->update(['previews' => serialize($previews)]);
    }

    public function deletePreview($preview_path)
    {
        if (\Storage::disk('public')->exists($preview_path)) {
            \Storage::disk('public')->delete($preview_path);
            
            $previews = $this->previews;
            
            if (($key = array_search($preview_path, $previews)) !== false) {
                unset($previews[$key]);
                $previews = array_values($previews);
            }

            return $this->update(['previews' => serialize($previews)]);
        }
    }

    public function isFree()
    {
        return $this->price == 0;
    }
}
