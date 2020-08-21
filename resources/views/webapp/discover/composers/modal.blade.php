@component('components.modal', ['id' => 'composers-modal', 'title' => 'Composers'])
@slot('body')
	<div class="list-group list-group-flush">
		@foreach($composers as $composer)
			<a href="{{route('webapp.search.results', ['search' => $composer->name])}}" class="list-group-item list-group-item-action">{{$composer->reversed_name}} ({{$composer->pieces_count}})</a>
		@endforeach
	</div>
@endslot
@endcomponent