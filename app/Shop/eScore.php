<?php

namespace App\Shop;

use App\Traits\FindBySlug;
use App\{ShareableContent, Admin};
use App\Merchandise\{Purchase, Product};
use App\Shop\Contract\Merchandise;
use Illuminate\Http\UploadedFile;

class eScore extends Product implements Merchandise
{
	use FindBySlug;

    protected $withCount = ['purchases'];
    protected $successRoute = 'escores.success';

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($escore) {
            $escore->topics()->detach();
            
            \Storage::disk('public')->delete($escore->cover_path);

            foreach ($escore->previews as $preview) {
                $escore->deletePreview($preview);        
            }
        });
    }

    public function topics()
    {
        return $this->belongsToMany(eScoreTopic::class);
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'item');
    }

    public function notification()
    {
        $icon = ! $this->isFree() ? '<i class="fas fa-money-bill-wave text-green mr-1"></i>' : null;
     
        return [
            'title' => 'eScore purchase',
            'message' => $icon . 'New purchase for the <strong>' . $this->title . '</strong> eScore.',
            'url' => ''
        ];
    }

    public function showRoute()
    {
        return route('escores.show', $this);
    }

    public function checkoutRoute()
    {
        return route('escores.checkout', $this);
    }

    public function downloadsView()
    {
        return view('components.shop.downloads.escore', ['product' => $this]);
    }

    public function links()
    {
        $pdf = $this->pdf_path ? ['PDF' => encrypt($this->pdf_path)] : [];
        $epub = $this->epub_path ? ['ePUB' => encrypt($this->epub_path)] : [];

        return array_merge($pdf, $epub);
    }

    public function url()
    {
        return route('escores.show', $this);
    }

    public function similar()
    {
        return [1,2,3,4];
    }

    public function getPriceInCentsAttribute()
    {
        return $this->price * 100;
    }

    // public function getPriceToHumansAttribute()
    // {
    //     $final = $this->finalPrice();

    //     if ($final == $this->price)

    //     return $final == $this->price ? 
    //         '$' . $this->price : 
    //         '<del class="opacity-6">$' . $this->price . '</del> $' . $final;
    // }

    public function finalPrice($inCents = false)
    {
        $price = floor($this->price - $this->applyDiscount());

        return $inCents ? $price * 100 : $price;
    }

    public function applyDiscount()
    {
        return $this->price * ($this->discount / 100);
    }

    public function getPreviewsAttribute($previews)
    {
        return $previews ? unserialize($previews) : [];
    }

    public function getPreviewsCountAttribute()
    {
        return count($this->previews);
    }

    public function aspectRatio()
    {
        if ($this->previews_count == 0)
            return 1;

        $size = getimagesize(storage($this->previews[0]));
        $width = $size[0];
        $height = $size[1];

        return $width / $height;
    }

    public function savePreview(UploadedFile $file)
    {
        $folder = "escores/{$this->id}";

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
        return $this->price == 0 || $this->discount == 100;
    }

    public function scopeDatatable($query)
    {
        return datatable($query)->withDate()->withBlade([
            'title' => view('admin.pages.escores.table.title'),
            'purchases_count' => view('admin.pages.escores.table.purchases'),
            'published' => view('admin.pages.escores.table.published'),
            'action' => view('admin.pages.escores.table.actions')
        ])->make();
    }
}
