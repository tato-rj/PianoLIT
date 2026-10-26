<tr>
	<td class="dataTables_main_column">{{$item->name}} ({{$item->alive_on}})</td>
	
	<td>{{$item->nationality}}</td>
	
	@include('admin.components.datatables.actions', ['actions' => [
		'edit' => route('admin.pianists.edit', $item->slug),
		'delete' => route('admin.pianists.destroy', $item->slug)
	]])
</tr>
