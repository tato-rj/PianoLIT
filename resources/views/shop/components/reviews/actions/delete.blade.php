<p class="text-muted">@icon('calendar')You submitted a review on {{$review->created_at->toFormattedDateString()}}</p>
@button([
	'label' => \App\Support\Icon::render('trash-2', ['mr' => 2]) . 'Delete my review',
	'styles' => [
		'size' => 'sm', 
		'theme' => 'red'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#delete-review-modal']])

@component('components.modal', ['id' => 'delete-review-modal', 'header' => 'Delete my review'])
@slot('body')
<form method="POST" action="{{route('reviews.destroy', $review)}}" class="text-left">
	@method('DELETE')
	@csrf
    Are you sure you want to do this?
    <p class="text-danger"><small>This action cannot be undone</small></p>
	@submit(['label' => 'Yes, delete my review', 'size' => 'sm', 'theme' => 'red'])
</form>
@endslot
@endcomponent
