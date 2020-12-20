@php($hasReviews = $product->publishedReviews()->withContent()->exists())

<div id="reviews" class="border-top pt-5 mb-5 {{$hasReviews ? 'row' : null}}">
	<div class="{{$hasReviews ? 'col-lg-4 col-md-6 col-12' : 'col-12'}} row mb-3">
		<div class="{{$hasReviews ? null : 'col-lg-3 col-md-4'}} col-12 mb-4">
			<h5>Overall rating</h5>
			@if($hasReviews)
			<p class="mb-2"><strong>{{$product->publishedReviews()->ratings()}}</strong> out of 5</p>
			@else
			<p>No ratings yet</p>
			@endif
			@include('shop.components.reviews.stars', ['complete' => true])
		</div>

		<div class="{{$hasReviews ? null : 'col-lg-4 col-md-8'}} col-12 mb-4">
			@for($i=5; $i>=1; $i--)
			@include('shop.components.reviews.bar')
			@endfor
		</div>

		<div class="{{$hasReviews ? null : 'col-lg-5'}} col-12 mb-4"> 
			<div class="text-center">
				@if(auth()->check() && $review = auth()->user()->reviewFor($product))
				@if($review->isPublished())
				@include('shop.components.reviews.actions.delete')
				@else
				@include('shop.components.reviews.actions.pending')
				@endif
				@else
				@include('shop.components.reviews.actions.new')
				@endif
			</div>
		</div>
	</div>

	@if($hasReviews)
	<div class="col-lg-8 col-md-6 col-12 mb-3"> 
		@foreach($product->publishedReviews()->withContent()->get() as $review)
			@include('shop.components.reviews.review')
		@endforeach
	</div>
	@endif
</div>

@component('components.modal', ['id' => 'review-modal', 'header' => 'My review'])
@slot('body')
<form method="POST" action="{{$product->reviewRoute()}}">
	@csrf
	<p class="text-center px-3">How would you rate <strong>{{$product->title}}</strong>?</p>
	@include('shop.components.reviews.stars', ['editable' => true, 'rating' => 0])
	<input type="hidden" name="rating" required>
	@input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Title (optional)', 'limit' => 50, 'required' => false])
	@textarea(['bag' => 'default', 'name' => 'content', 'placeholder' => 'Review (optional)', 'limit' => 220, 'rows' => 3, 'required' => false])
	@input(['bag' => 'default', 'name' => 'reviewer', 'placeholder' => 'Your name (leave blank to review anonymously)', 'limit' => 50, 'required' => false])
	@submit(['label' => 'Submit my review', 'block' => true])
</form>
@endslot
@endcomponent
