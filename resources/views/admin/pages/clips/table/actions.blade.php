<div class="text-right"> 
	@include('components.datatable.actions', ['actions' => [
		'edit' => route('admin.clips.edit', $item->slug),
		'delete' => route('admin.clips.destroy', $item->slug)
	]])
</div>