@component('components.draggable.cards.small', ['model' => $playlist])

<span class="badge alert-{{$playlist->is_featured ? 'green' : 'grey'}} align-text-bottom badge-pill">{{$playlist->order + 1}}</span>
<span>{{$playlist->name}} <small class="text-muted">&middot; {{$playlist->pieces_count}} pieces</small></span>

@slot('controls')
	<div class="d-flex">
		<a href="{{route('admin.playlists.edit', $playlist)}}" class="btn btn-sm btn-warning mr-2">Edit</a>
		<a href="#" data-url="{{route('admin.playlists.destroy', $playlist->id)}}" title="Delete" data-toggle="modal" data-target="#delete-modal" class="btn btn-sm btn-danger delete d-none d-sm-block">
			Remove
		</a>
	</div>
@endslot
@endcomponent
