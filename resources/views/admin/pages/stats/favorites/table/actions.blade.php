<div class="text-right">
	<button class="btn btn-outline-secondary btn-sm" {{$item->favorites_count == 0 ? 'disabled' : null}} data-target="#view-pieces-{{$item->id}}" data-toggle="modal">@fa(['icon' => 'eye']) View pieces</button>

	@component('components.modal', ['id' => 'view-pieces-'.$item->id, 'header' => 'Folder pieces'])
	@slot('body')
	<div class="text-left">
		@foreach($item->favorites as $favorite)
		<div class="d-flex align-items-center {{$loop->last ?  null : 'mb-2'}}">
			<div class="badge badge-pill alert-blue mr-2">{{$loop->iteration}}</div>
			<div>{{$favorite->piece->medium_name_with_composer}}</div>
		</div>
		@endforeach
	</div>
	@endslot
	@endcomponent
</div>
