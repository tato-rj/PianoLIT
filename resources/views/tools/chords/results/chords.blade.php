@if($request['results']['main_content'])
<label class="text-muted text-muted"><strong>THE CHORD IS MOST LIKELY{{count($request['results']['main_content']) > 1 ? ' ONE OF THESE...' : null}}</strong></label>
<div class="chords-results d-flex flex-wrap">
@foreach($request['results']['main_content'] as $chord)
	<button style="font-size: 1.5em" class="m-1 btn btn-teal" data-notes="{{json_encode($chord['notes'])}}">
		<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
	</button>
@endforeach
</div>
@endif

@if($request['results']['additional_content'])
<label class="text-muted text-muted">While less likely, it may also be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
<div class="chords-results d-flex flex-wrap">
@foreach($request['results']['additional_content'] as $chord)
	<button class="btn btn btn-outline-secondary m-1" data-notes="{{json_encode($chord['notes'])}}">
		<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
	</button>
@endforeach
</div>
@endif