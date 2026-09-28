@component('components.modal', ['id' => 'journey-modal', 'headerNoborder' => true])
@slot('body')
@foreach($journey as $playlist)
<div class="text-center mb-4">
	<a href="{{route('webapp.playlists.show', $playlist)}}" class="link-none">
		<img src="{{asset('images/webapp/icons/journey/'.$loop->index.'.svg')}}" style="width: 70px" class="mb-3 shadow rounded-circle">
		<h5 class="mb-1">{{$playlist->name}}</h5>
		<p class="mb-2">{{$playlist->subtitle}}</p>
		@pill(['theme' => 'grey', 'label' => $playlist->pieces_count . ' PIECES'])
	</a>
	@if(!$loop->last)
	<div class="d-flex flex-column justify-content-center mt-2" style="font-size: 44%">
		@icon('circle', ['color' => 'dark', 'mr' => 0, 'classes' => 'my-2', 'filled' => true])
		@icon('circle', ['color' => 'dark', 'mr' => 0, 'classes' => 'my-2', 'filled' => true])
		@icon('circle', ['color' => 'dark', 'mr' => 0, 'classes' => 'my-2', 'filled' => true])
	</div>
	@endif
</div>
@endforeach
@endslot
@endcomponent