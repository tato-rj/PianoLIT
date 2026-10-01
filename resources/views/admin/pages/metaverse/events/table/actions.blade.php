<div class="text-end">
	@component('components.datatable.actions', ['actions' => [
		'delete' => route('admin.metaverse.event.destroy', $item->id)
	]])

		<a class="text-muted me-2 align-middle" href="{{$item->url}}" target="_blank">@icon('eye', ['mr' => 0, 'classes' => 'align-middle'])</a>
		<a href="" data-bs-toggle="modal" data-bs-target="#edit-{{$item->id}}-modal" title="Edit" class="text-muted me-2">@icon('square-pen', ['mr' => 0, 'classes' => 'align-middle'])</a>

		@include('admin.pages.metaverse.events.edit', ['event' => $item])
	@endcomponent
</div>