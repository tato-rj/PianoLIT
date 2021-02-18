@php($isSmall = isset($sm) && $sm)
@php($makeItFree = ! empty($free) && $free)

<h4 class="m-0" style="{{$isSmall ? 'font-size:88%' : null }}">
	<span>{{$isSmall ? null : 'Price:'}}</span>
	<span class="{{$product->discount ? 'text-red' : null}}">
		@if($product->isFree() || $makeItFree)
			<span class="text-green">FREE!</span>
			@if($makeItFree)
			<small class="opacity-6 text-muted"><del>${{$product->price}}</del></small>
			@endif
		@else
		${{$product->finalPrice()}}
		@endif 
		@if($product->discount && ! $makeItFree)
			<small class="opacity-6 text-muted"><del>${{$product->price}}</del></small>
		@endif
	</span>
</h4>