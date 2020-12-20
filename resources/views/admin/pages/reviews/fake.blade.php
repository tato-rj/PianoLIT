<div class="text-right">
@button([
	'label' => '<i class="fas fa-smile-wink mr-1"></i>Write a fake review', 
	'styles' => [
		'theme' => 'warning'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#review-modal']])
</div>

@component('components.modal', ['id' => 'review-modal', 'header' => 'My review'])
@slot('body')
<form method="POST" action="{{$product->reviewRoute('admin')}}">
	@csrf
	<p class="text-center px-3">How would you rate <strong>{{$product->title}}</strong>?</p>
	@include('shop.components.reviews.stars', ['editable' => true, 'rating' => 0])
	<input type="hidden" name="rating" required>
	@input(['bag' => 'default', 'name' => 'title', 'placeholder' => 'Title (optional)', 'limit' => 50, 'required' => false])
	@textarea(['bag' => 'default', 'name' => 'content', 'placeholder' => 'Review (optional)', 'limit' => 220, 'rows' => 3, 'required' => false])
	@input(['bag' => 'default', 'name' => 'reviewer', 'placeholder' => 'Reviewer name (leave blank to review anonymously)', 'limit' => 50, 'required' => false])
	@submit(['label' => 'Submit fake review', 'block' => true])
</form>
@endslot
@endcomponent