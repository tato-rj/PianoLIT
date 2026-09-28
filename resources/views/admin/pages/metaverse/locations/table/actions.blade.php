<div class="text-right"> 
	@component('components.datatable.actions', ['actions' => [
		'delete' => route('admin.metaverse.locations.destroy', $item->id)
	]])

		<a class="text-muted mr-2 align-middle" href="{{$item->url}}" target="_blank">@icon('eye', ['mr' => 0, 'classes' => 'align-middle'])</a>
		<a href="" data-toggle="modal" data-target="#edit-{{$item->id}}-modal" title="Edit" class="text-muted mr-2">@icon('square-pen', ['mr' => 0, 'classes' => 'align-middle'])</a>

		@include('admin.pages.metaverse.locations.edit', ['event' => $item])
	@endcomponent
</div>