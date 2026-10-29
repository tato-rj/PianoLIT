<?php

namespace App\Merchandise;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

/** Physical merchandise stays independent of legacy digital-product purchases. */
class MerchandiseProduct extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['variants' => 'array', 'synced_at' => 'datetime', 'revision' => 'integer'];

    protected static function booted()
    {
        static::creating(function ($product) {
            if ($product->slug) return;
            $base = \App\Services\Shop\ProductNames::slug($product->name) ?: 'product';
            $slug = $base;
            $suffix = 2;
            while (static::withTrashed()->where('printful_store_id', $product->printful_store_id)->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            $product->slug = $slug;
        });
    }

    public function images()
    {
        return $this->hasMany(MerchandiseImage::class, 'product_id')->orderBy('position')->orderBy('id');
    }

    public function mainImage()
    {
        return $this->belongsTo(MerchandiseImage::class, 'main_image_id');
    }
}
