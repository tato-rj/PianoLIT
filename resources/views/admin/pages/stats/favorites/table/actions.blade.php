<div class="text-end">
	<button class="btn btn-outline-secondary btn-sm" {{$item->favorites_count == 0 ? 'disabled' : null}} data-bs-target="#view-pieces-{{$item->id}}" data-bs-toggle="modal">@icon('eye') View pieces</button>

	@component('components.modal', ['id' => 'view-pieces-'.$item->id, 'header' => 'Folder pieces'])
	@slot('body')
	<div class="text-start">
		@foreach($item->favorites as $favorite)
		<div class="d-flex align-items-center {{$loop->last ?  null : 'mb-2'}}">
			<div class="badge rounded-pill alert-blue me-2">{{$loop->iteration}}</div>
			<div>{{$favorite->piece->medium_name_with_composer}}</div>
		</div>
		@endforeach
	</div>
	@endslot
	@endcomponent
</div>
