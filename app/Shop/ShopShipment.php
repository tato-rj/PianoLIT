<?php

namespace App\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopShipment extends Model
{
    protected $table = 'shop_shipments';
    protected $guarded = [];
    protected $casts = ['items' => 'array', 'events' => 'array', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'estimated_from' => 'date', 'estimated_to' => 'date'];

    public function order() { return $this->belongsTo(ShopOrder::class, 'order_id'); }
}
