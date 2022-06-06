@component('components.modal', ['id' => 'composers-modal'])
@slot('header')
Composers
@endslot

@slot('body')
	<div class="list-group list-group-flush">
		@foreach($composers as $composer)
			<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="list-group-item list-group-item-action"><img src="{{$composer->cover_image}}" class="rounded-circle mr-2" style="width: 34px" alt="{{$composer->name}}"><span class="align-middle">{{$composer->reversed_name}} ({{$composer->pieces_count}})</span></a>
		@endforeach
	</div>
@endslot
@endcomponent