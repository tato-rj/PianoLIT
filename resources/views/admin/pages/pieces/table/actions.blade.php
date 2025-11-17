{{-- <div class="text-right"> 
@component('components.datatable.actions', ['actions' => [
	'edit' => route('admin.pieces.edit', $item->id),
	'delete' => route('admin.pieces.destroy', $item->id)
	]])
	@if($item->is_free)
	<div>
		<button class="border-0 p-0 bg-transparent text-warning mr-2 align-middle" title="This piece is the current Freepick!" disabled>@fa(['icon' => 'award', 'mr' => 0])</button>
	</div>
	@elseif($item->hasImage() && ! $item->highlighted_at)
	<form method="POST" action="{{route('admin.pieces.highlight', $item->id)}}">
		@csrf
		@method('PATCH')
		<button type="submit" class="border-0 p-0 bg-transparent text-grey mr-2 align-middle" title="Highlight this piece">@fa(['icon' => 'award', 'mr' => 0])</button>
	</form>
	@elseif($item->highlighted_at)
	<div>
		<button class="border-0 p-0 bg-transparent text-success mr-2 align-middle" title="This piece has already been selected as a Freepick" disabled>@fa(['icon' => 'check', 'mr' => 0])</button>
	</div>
	@endif

	<form method="POST" action="{{route('admin.pieces.hijack', $item->id)}}">
		@csrf
		@method('PATCH')
		<button type="submit" class="border-0 p-0 bg-transparent text-muted mr-2 align-middle" title="Change this piece creation date to today"><i class="fas fa-calendar-day"></i></button>
	</form>
@endcomponent
</div> --}}