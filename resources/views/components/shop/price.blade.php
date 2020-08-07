<h4 class="m-0">
	<span>Price:</span>
	<span class="{{$product->discount ? 'text-red' : null}}">
		@if($product->isFree())
		FREE!
		@else
		${{$product->finalPrice()}}
		@endif 
		@if($product->discount)
			<small class="opacity-6 text-muted"><del>${{$product->price}}</del></small>
		@endif
	</span>
</h4>