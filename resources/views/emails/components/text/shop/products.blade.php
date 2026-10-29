@foreach($items as $product)
- {{ $product['name'] }}{{ empty($product['option']) ? '' : ' · '.$product['option'] }}{{ isset($product['quantity']) ? ' × '.$product['quantity'] : '' }}{{ ($showPrices ?? false) ? ' — '.app(\App\Services\Shop\Storefront::class)->money($product['unit_price'] * $product['quantity'], strtoupper($currency)) : '' }}

@endforeach
