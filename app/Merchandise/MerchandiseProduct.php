<?php

namespace App\Merchandise;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};

/** Physical merchandise stays independent of legacy digital-product purchases. */
class MerchandiseProduct extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['variants' => 'array', 'synced_at' => 'datetime', 'revision' => 'integer'];

    public function images()
    {
        return $this->hasMany(MerchandiseImage::class, 'product_id')->orderBy('position')->orderBy('id');
    }

    public function mainImage()
    {
        return $this->belongsTo(MerchandiseImage::class, 'main_image_id');
    }
}
