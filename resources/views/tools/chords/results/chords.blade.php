<div class="mb-4">
@if($request['results']['main_content'])
	<label class="text-muted"><strong>THE CHORD IS MOST LIKELY{{count($request['results']['main_content']) > 1 ? ' ONE OF THESE...' : null}}</strong></label>
	<div class="chords-results d-flex flex-wrap">
	@foreach($request['results']['main_content'] as $chord)
		<button class="m-1 btn btn-chord-main" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	</div>
@endif
</div>
<div class="mb-4">
@if($request['results']['additional_content'])
	@if($request['results']['main_content'])
	<label class="text-muted m-0">Other arrangements of these notes are probably inversions of the {{count($request['results']['main_content']) == 1 ? 'chord' : 'chords'}} above.<br>Therefore, while less likely, the chord may also be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
	@else
	<label class="text-muted text-muted">These notes don't quite form any typical chord, but it may be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
	@endif

	<div class="chords-results d-flex flex-wrap">
	@foreach($request['results']['additional_content'] as $chord)
		<button class="btn btn-light btn-chord-additional m-1" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	</div>
@endif
</div>