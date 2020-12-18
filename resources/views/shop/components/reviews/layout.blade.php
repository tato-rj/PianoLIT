@forelse($product->reviews()->published()->get() as $review)
	@include('shop.components.reviews.review')
@empty
	<div>Be the first to review this product!</div>
@endforelse