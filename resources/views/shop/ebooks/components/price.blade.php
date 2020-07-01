<h4 class="{{$ebook->discount ? 'text-red' : null}} m-0">
	@if($ebook->isFree())
	FREE!
	@else
	${{$ebook->finalPrice()}}
	@endif 
	@if($ebook->discount)
		<small class="opacity-6 text-muted"><del>${{$ebook->price}}</del></small>
	@endif
</h4>