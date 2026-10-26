<div class="text-end">
	@include('admin.components.datatables.actions', ['actions' => [
		'edit' => route('admin.clips.edit', $item->slug),
		'delete' => route('admin.clips.destroy', $item->slug)
	]])
</div>