<div class="text-end">
	@include('admin.components.datatables.actions', ['actions' => [
		'edit' => route('admin.pianists.edit', $item->slug),
		'delete' => route('admin.pianists.destroy', $item->slug)
	]])
</div>