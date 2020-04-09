<div class="text-right">
	@component('components.datatable.actions', ['actions' => [
		'edit' => route('admin.infographs.edit', $item->slug),
		'delete' => route('admin.infographs.destroy', $item->slug)
	]])
	<a href="#" data-toggle="modal" title="Preview this infograph" data-thumbnail="{{storage($item->thumbnail_path)}}" data-image="{{storage($item->cover_path)}}" data-target="#item-preview" class="text-muted mr-2">
		<i class="fas fa-eye align-middle"></i>
	</a>
	@endcomponent
</div>
