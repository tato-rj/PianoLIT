<div class="text-right"> 
	@component('components.datatable.actions', ['actions' => [
		'delete' => route('admin.metaverse.event.destroy', $item->id)
	]])

		<a class="text-muted mr-2 align-middle" href="{{$item->url}}" target="_blank">@fa(['icon' => 'eye', 'mr' => 0, 'classes' => 'align-middle'])</a>
		<a href="" data-toggle="modal" data-target="#edit-{{$item->id}}-modal" title="Edit" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>

		@include('admin.pages.metaverse.events.edit', ['event' => $item])
	@endcomponent
</div>