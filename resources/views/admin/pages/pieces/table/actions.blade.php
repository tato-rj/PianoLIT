<div class="text-right"> 
@component('components.datatable.actions', ['actions' => [
	'edit' => route('admin.pieces.edit', $item->id),
	'delete' => route('admin.pieces.destroy', $item->id)
	]])
	@if($item->is_free)
	<div>
		<button class="border-0 p-0 bg-transparent text-success mr-2 align-middle" disabled><i class="fas fa-award"></i></button>
	</div>
	@elseif($item->hasImage() && ! $item->highlighted_at)
	<form method="POST" action="{{route('admin.pieces.highlight', $item->id)}}">
		@csrf
		@method('PATCH')
		<button type="submit" class="border-0 p-0 bg-transparent text-grey mr-2 align-middle" title="Highlight this piece"><i class="fas fa-award"></i></button>
	</form>
	@elseif($item->highlighted_at)
	<div>
		<button class="border-0 p-0 bg-transparent text-success mr-2 align-middle" disabled><i class="fas fa-check-circle"></i></button>
	</div>
	@endif

	<form method="POST" action="{{route('admin.pieces.hijack', $item->id)}}">
		@csrf
		@method('PATCH')
		<button type="submit" class="border-0 p-0 bg-transparent text-muted mr-2 align-middle" title="Change this piece creation date to today"><i class="fas fa-calendar-day"></i></button>
	</form>
@endcomponent
</div>