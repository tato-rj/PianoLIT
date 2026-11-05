<button type="button" class="btn btn-red btn-sm" title="Delete my review" aria-label="Delete my review" data-bs-toggle="modal" data-bs-target="#delete-review-modal">@icon('trash-2', ['mr' => 0])</button>

@component('components.dialogs.modal', ['id' => 'delete-review-modal', 'header' => 'Delete my review'])
@slot('body')
<form method="POST" action="{{route('reviews.destroy', $review)}}" class="text-start">
	@method('DELETE')
	@csrf
    Are you sure you want to do this?
    <p class="text-danger"><small>This action cannot be undone</small></p>
    <button type="submit" class="btn btn-red">Yes, delete my review</button>
</form>
@endslot
@endcomponent
