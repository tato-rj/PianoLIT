<h4 class="{{$product->discount ? 'text-red' : null}} m-0">
	@if($product->isFree())
	FREE!
	@else
	${{$product->finalPrice()}}
	@endif 
	@if($product->discount)
		<small class="opacity-6 text-muted"><del>${{$product->price}}</del></small>
	@endif
</h4>