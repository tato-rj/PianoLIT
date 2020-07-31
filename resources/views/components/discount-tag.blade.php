@if($product->discount > 0 && $product->discount < 100)
<div class="absolute-top-{{$position}} bg-red text-white px-2 py-1">
	<span><strong>{{$product->discount}}%</strong> OFF!</span>
</div>
@endif