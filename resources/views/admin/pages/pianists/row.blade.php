<tr>
	<td>{{$pianist->name}} ({{$pianist->alive_on}})</td>
	<td>{{$pianist->nationality}}</td>
	<td class="text-right" style="white-space: nowrap;">
		<a href="{{route('admin.pianists.edit', $pianist->slug)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
		@can('update', $pianist)
		<a href="" data-name="{{$pianist->name}}" data-url="{{route('admin.pianists.destroy', $pianist->slug)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
		@endcan
	</td>
</tr>
