<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:20px 0;border-collapse:collapse;">
@foreach(app(\App\Services\Shop\EmailProducts::class)->items($items, $orderItems ?? []) as $product)
<tr>
@if($product['image'])
<td width="88" valign="middle" style="width:88px;padding:12px 16px 12px 0;border-bottom:1px solid #edf2f7;">
<img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" width="72" style="display:block;width:72px;max-width:72px;height:auto;border:0;">
</td>
@endif
<td @if(!$product['image']) colspan="2" @endif valign="middle" style="padding:12px 0;border-bottom:1px solid #edf2f7;color:#3d4852;font-size:16px;line-height:1.5;text-align:left;">
<strong>{{ $product['name'] }}</strong>
@if(!empty($product['option']))<br><span style="color:#74787e;">{{ $product['option'] }}</span>@endif
@if(isset($product['quantity']))
<br><span style="color:#74787e;">Qty {{ $product['quantity'] }}@if($showPrices ?? false) · {{ app(\App\Services\Shop\Storefront::class)->money($product['unit_price'] * $product['quantity'], strtoupper($currency)) }}@endif</span>
@endif
</td>
</tr>
@endforeach
</table>
