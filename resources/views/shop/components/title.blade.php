@if($html = $product->own('title'))
{!! $html !!}
@else
<h4 class="clamp-2"><strong>{{$product->title}}</strong></h4>
@endif