<div class="position-relative mx-auto d-block w-100" style="max-width: 280px">
	<img src="{{$product->cover_image()}}" class="w-100 book-cover">
	@if($product->discount > 0 && $product->discount < 100)
	<div class="position-absolute bg-red text-white px-2 py-1" style="top: .65em; left: -1em; transform: rotate(-45deg);">
		<span><strong>{{$product->discount}}%</strong> OFF!</span>
	</div>
	@endif
</div>