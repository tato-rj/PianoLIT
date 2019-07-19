<div style="display: none;" id="subtitle-results">
	<div>The notes you gave us are <strong class="text-dark">{{implode(' - ', $request['notes'])}}</strong>
	@if($request['enharmonic'])
	or <strong class="text-dark">{{implode(' - ', $request['enharmonic'])}}</strong>
	@endif
	</div>
	<div>Here are the chords we could make with them!</div>
</div>
@if($request['results']['main_content'] || $request['enharmonicResults']['main_content'])
<div class="mb-4">
	<label class=""><strong>THE CHORD IS MOST LIKELY{{count($request['results']['main_content']) > 1 ? ' ONE OF THESE...' : null}}</strong></label>
	<div class="chords-results d-flex flex-wrap">
	@foreach($request['results']['main_content'] as $chord)
		<button class="m-1 btn btn-chord-main" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	@if($request['enharmonic'])
	@foreach($request['enharmonicResults']['main_content'] as $chord)
		<button class="m-1 btn btn-chord-main" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	@endif
	</div>
</div>
@endif
@if($request['results']['additional_content'] || $request['enharmonicResults']['additional_content'])
<div class="mb-4">
	@if($request['results']['main_content'])
	<label class="">Other options are probably inversions of the {{count($request['results']['main_content']) == 1 ? 'chord' : 'chords'}} above.<br>Therefore, while less likely, the chord may also be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
	@else
	<label class="">The chord isn't quite complete, but it may be{{count($request['results']['additional_content']) > 1 ? ' one of these' : null}}...</label>
	@endif

	<div class="chords-results d-flex flex-wrap">
	@foreach($request['results']['additional_content'] as $chord)
		<button class="btn btn-chord-additional m-1" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	@if($request['enharmonic'])
	@foreach($request['enharmonicResults']['additional_content'] as $chord)
		<button class="btn btn-chord-additional m-1" data-notes="{{json_encode($chord['notes'])}}">
			<i class="fas fa-play-circle mr-2 opacity-4"></i><strong>{{$chord['name']}}</strong>
		</button>
	@endforeach
	@endif
	</div>
</div>
@endif