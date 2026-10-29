<?php

namespace App\Shop;

use Illuminate\Database\Eloquent\Model;

class ShopOrder extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['owner_hash', 'recipient'];
    protected $casts = ['items' => 'array', 'recipient' => 'encrypted:array',
        'subtotal' => 'integer', 'shipping' => 'integer', 'tax' => 'integer', 'total' => 'integer', 'fulfillment_cost' => 'integer',
        'expires_at' => 'datetime', 'paid_at' => 'datetime', 'submitted_at' => 'datetime', 'tracking_checked_at' => 'datetime'];

    public function shipments() { return $this->hasMany(ShopShipment::class, 'order_id')->orderBy('id'); }
    public function emails() { return $this->hasMany(ShopOrderEmail::class, 'order_id')->orderBy('id'); }
}
