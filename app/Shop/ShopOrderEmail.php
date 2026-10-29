<?php

namespace App\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopOrderEmail extends Model
{
    protected $table = 'shop_order_emails';
    protected $guarded = [];
    protected $casts = ['claimed_at' => 'datetime', 'sent_at' => 'datetime'];

    public function order() { return $this->belongsTo(ShopOrder::class, 'order_id'); }
    public function shipment() { return $this->belongsTo(ShopShipment::class, 'shipment_id'); }
}
