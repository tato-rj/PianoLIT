@php($editable = isset($editable) && $editable)
@php($rating = $rating ?? $product->publishedReviews()->ratings())
@php($isSmall = isset($sm) && $sm)
@php($classes = $editable ? 'pr-1 animated editable-star cursor-pointer' : 'pr-1 animated')

<div class="d-flex align-items-center mb-{{$mb ?? 2}} {{$editable ? 'justify-content-center mb-4' : null}}" 
	@if($isSmall)
	style="font-size:86%"
	@elseif($editable)
	style="font-size: 180%"
	@endif
	>
	@for($i=1; $i<=5; $i++)
		@if($rating >= $i)
			@icon('star', ['mr' => 0, 'color' => 'warning', 'classes' => $classes, 'filled' => true])
		@else
			@if($i - $rating < 1)
			@icon('star-half', ['mr' => 0, 'color' => 'warning', 'classes' => $classes, 'filled' => true])
			@else
			@icon('star', ['mr' => 0, 'color' => 'warning', 'classes' => $classes])
			@endif
		@endif
	@endfor
	@if(isset($complete) && $complete)
	<span class="ml-1"><small>({{$product->publishedReviews()->count()}} {{str_plural('review', $product->publishedReviews()->count())}})</small></span>
	@endif
</div>
