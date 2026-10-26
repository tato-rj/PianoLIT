<div class="text-end">
@if($item->hasContent())
@include('admin.components.table-action', ['tableAction' => ['label' => 'Details', 'attributes' => ['data-bs-toggle' => 'modal', 'data-bs-target' => '#details-modal-'.$item->id]]])

@component('components.dialogs.modal', ['id' => 'details-modal-'.$item->id, 'header' => 'Review'])
@slot('body')
<div class="text-start">
	@include('shop.components.reviews.stars', ['rating' => $item->rating])
	<h6>{!! $item->title ?? '<i class="text-muted">No title</i>' !!}</h6>
	<p>{!! $item->content ?? '<i class="text-muted">No content</i>' !!}</p>
</div>
@endslot
@endcomponent
@endif

@include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'iconOnly' => false, 'attributes' => ['data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal-'.$item->id]]])

@component('components.dialogs.modal', ['id' => 'delete-modal-'.$item->id, 'header' => 'Delete this review'])
@slot('body')
<form method="POST" action="{{route('admin.reviews.destroy', $item)}}" class="text-start">
	@method('DELETE')
	@csrf
    Are you sure you want to do this?
    <p class="text-danger"><small>This action cannot be undone</small></p>
	@include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Yes, delete this review', 'iconOnly' => false]])
</form>
@endslot
@endcomponent
</div>