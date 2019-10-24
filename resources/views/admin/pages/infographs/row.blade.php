<tr>
	<td>{{$infograph->name}}</td>
	<td>{{ucfirst($infograph->type)}}</td>
	<td>{{$infograph->downloads}}</td>
	<td>{{$infograph->score}}</td>
	<td class="text-right" style="white-space: nowrap;">
		<a href="#" data-toggle="modal" data-image="{{storage($infograph->thumbnail_path)}}" data-target="#infograph-preview" class="text-muted mr-2"><i class="fas fa-eye align-middle"></i></a>
		<a href="{{route('admin.infographs.edit', $infograph->slug)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
		@can('update', $infograph)
		<a href="" data-name="{{$infograph->name}}" data-url="{{route('admin.infographs.destroy', $infograph->slug)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
		@endcan
	</td>
	<td class="text-right">
		@include('admin.components.toggle.infograph')
	</td>
</tr>