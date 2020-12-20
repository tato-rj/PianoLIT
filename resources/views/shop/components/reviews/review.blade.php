<div class="mb-4">
	<div class="text-muted d-flex d-apart mb-2">
		<small>{{$review->created_at->diffForHumans()}}</small>
		<small>{!! $review->isAnonymous() ? 'by ' . $review->reviewer : '<i>Anonymous</i>' !!}</small>
	</div>
	@include('shop.components.reviews.stars', ['rating' => $review->rating])
	@if($review->title)
	<h6 class="mb-1">{{$review->title}}</h6>
	@endif
	@if($review->content)
	<p class="mb-1">{{$review->content}}</p>
	@endif
</div>