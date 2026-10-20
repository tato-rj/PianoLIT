<div class="text-end">
	@component('components.datatable.actions', ['actions' => [
		'edit' => route('admin.infographs.edit', $item->slug),
		'delete' => route('admin.infographs.destroy', $item->slug)
	]])
	@include('admin.components.table-action', ['tableAction' => ['label' => 'Preview this infograph', 'icon' => 'eye', 'iconOnly' => true, 'attributes' => ['data-bs-toggle' => 'modal', 'data-thumbnail' => storage($item->thumbnail_path), 'data-image' => storage($item->cover_path), 'data-bs-target' => '#item-preview']]])
	@endcomponent
</div>
