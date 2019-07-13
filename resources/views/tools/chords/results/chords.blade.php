@if($request['results']['main_content'])
<label class="text-muted text-muted"><strong>THIS CHORD IS MOST LIKELY{{count($request['results']['main_content']) > 1 ? ' ONE OF THESE' : null}}</strong></label>
<div class="chords-results d-flex flex-wrap">
@foreach($request['results']['main_content'] as $chord)
	<button style="font-size: 2em" class="m-1 btn btn-lg btn-wide btn-teal">
		<strong>{{$chord['name']}}</strong>
	</button>
@endforeach
</div>
@endif

@if($request['results']['additional_content'])
<label class="text-muted text-muted">While less likely, it may also be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
<div class="chords-results d-flex flex-wrap">
@foreach($request['results']['additional_content'] as $chord)
	<button class="btn btn btn-outline-secondary m-1">
		<strong>{{$chord['name']}}</strong>
	</button>
@endforeach
</div>
@endif