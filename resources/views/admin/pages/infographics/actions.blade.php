<div class="text-end">
	@component('components.datatable.actions', ['actions' => [
		'edit' => route('admin.infographs.edit', $item->slug),
		'delete' => route('admin.infographs.destroy', $item->slug)
	]])
	<a href="#" data-bs-toggle="modal" title="Preview this infograph" data-thumbnail="{{storage($item->thumbnail_path)}}" data-image="{{storage($item->cover_path)}}" data-bs-target="#item-preview" class="text-muted me-2">
		@icon('eye', ['mr' => 0, 'classes' => 'align-middle'])
	</a>
	@endcomponent
</div>
