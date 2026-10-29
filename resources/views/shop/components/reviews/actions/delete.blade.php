<p class="text-muted">@icon('calendar')You submitted a review on {{$review->created_at->toFormattedDateString()}}</p>
@if($storefront ?? false)
<button type="button" class="shop-button" data-bs-toggle="modal" data-bs-target="#delete-review-modal">@icon('trash-2')Delete my review</button>
@else
@button([
	'label' => \App\Support\Icon::render('trash-2', ['mr' => 2]) . 'Delete my review',
	'styles' => [
		'size' => 'sm',
		'theme' => 'red'
		],
	'classes' => 'rounded',
	'data' => ['bs-toggle' => 'modal', 'bs-target' => '#delete-review-modal']])
@endif

@component('components.dialogs.modal', ['id' => 'delete-review-modal', 'header' => 'Delete my review'])
@slot('body')
<form method="POST" action="{{route('reviews.destroy', $review)}}" class="text-start">
	@method('DELETE')
	@csrf
    Are you sure you want to do this?
    <p class="text-danger"><small>This action cannot be undone</small></p>
    @if($storefront ?? false)
        <button type="submit" class="shop-button shop-button--dark">Yes, delete my review</button>
    @else
        @submit(['label' => 'Yes, delete my review', 'size' => 'sm', 'theme' => 'red'])
    @endif
</form>
@endslot
@endcomponent
