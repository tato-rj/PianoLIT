<label class="text-muted text-muted"><strong>THIS CHORD IS MOST LIKELY</strong></label>

@if($request['results']['main_content'])
<div class="chords-results d-flex">
@foreach($request['results']['main_content'] as $chord)
	<h2>
		<strong>{{$chord['name']}}</strong>
	</h2>
@endforeach
</div>
@endif

@if($request['results']['additional_content'])
<label class="text-muted text-muted">While less likely, it may be one of the following...</label>
<div class="chords-results d-flex">
@foreach($request['results']['additional_content'] as $chord)
	<h6 class="border rounded px-3 py-2 m-1 text-muted">
		<strong>{{$chord['name']}}</strong>
	</h6>
@endforeach
</div>
@endif