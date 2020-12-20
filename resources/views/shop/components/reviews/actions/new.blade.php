@if(! $hasReviews)
<p class="text-muted">Be the first to write a review!</p>
@endif
@button([
	'label' => 'Write your review', 
	'styles' => [
		'size' => 'wide', 
		'theme' => 'primary'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#review-modal']])