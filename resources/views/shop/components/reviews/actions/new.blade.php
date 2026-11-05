@if(! $hasReviews)
<p class="text-muted">No reviews yet. You can write a review below.</p>
@endif
@button([
	'label' => 'Write your review', 
	'styles' => [
		'size' => 'wide', 
		'theme' => 'primary'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#review-modal']])