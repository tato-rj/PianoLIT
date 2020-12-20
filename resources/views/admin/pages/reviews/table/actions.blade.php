<div class="text-right">
@if($item->hasContent())
@button([
	'label' => 'Details', 
	'styles' => [
		'size' => 'sm', 
		'theme' => 'grey'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#details-modal-'.$item->id]])

@component('components.modal', ['id' => 'details-modal-'.$item->id, 'header' => 'Review'])
@slot('body')
<div class="text-left">
	@include('shop.components.reviews.stars', ['rating' => $item->rating])
	<h6>{!! $item->title ?? '<i class="text-muted">No title</i>' !!}</h6>
	<p>{!! $item->content ?? '<i class="text-muted">No content</i>' !!}</p>
</div>
@endslot
@endcomponent
@endif

@button([
	'label' => '<i class="fa fas fa-trash-alt mr-2"></i>Delete', 
	'styles' => [
		'size' => 'sm', 
		'theme' => 'red'
		], 
	'classes' => 'rounded', 
	'data' => ['toggle' => 'modal', 'target' => '#delete-modal-'.$item->id]])

@component('components.modal', ['id' => 'delete-modal-'.$item->id, 'header' => 'Delete this review'])
@slot('body')
<form method="POST" action="{{route('admin.reviews.destroy', $item)}}" class="text-left">
	@method('DELETE')
	@csrf
    Are you sure you want to do this?
    <p class="text-danger"><small>This action cannot be undone</small></p>
	@submit(['label' => 'Yes, delete this review', 'size' => 'sm', 'theme' => 'red'])
</form>
@endslot
@endcomponent
</div>