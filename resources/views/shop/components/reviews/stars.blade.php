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
			@fa(['fa_type' => 's', 'mr' => 0, 'icon' => 'star', 'color' => 'warning', 'classes' => $classes])
		@else
			@if($i - $rating < 1)
			@fa(['fa_type' => 's', 'mr' => 0, 'icon' => 'star-half-alt', 'color' => 'warning', 'classes' => $classes])
			@else
			@fa(['fa_type' => 'r', 'mr' => 0, 'icon' => 'star', 'color' => 'warning', 'classes' => $classes])
			@endif
		@endif
	@endfor
	@if(isset($complete) && $complete)
	<span class="ml-1"><small>({{$product->publishedReviews()->count()}} {{str_plural('review', $product->publishedReviews()->count())}})</small></span>
	@endif
</div>
