<div class="text-right"> 
	@include('components.datatable.actions', ['actions' => [
		'edit' => route('admin.pianists.edit', $item->slug),
		'delete' => route('admin.pianists.destroy', $item->slug)
	]])
</div>