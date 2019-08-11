@if($request['has_relevant'])
<div class="mb-4">
	<label class=""><strong>THE CHORD IS MOST LIKELY...</strong></label>
	<div class="chords-results d-flex flex-wrap">
	@foreach($request['chords'] as $chord)
		@foreach($chord['inversions'] as $inversion)
			@if($inversion['ranking'] <= $request['most_relevant'] + 1)
				<button class="m-1 btn btn-chord-main" 
					href="#{{str_replace('#', '', chordToHumans($inversion['label']['full_shorthand']))}}" 
					data-notes="{{json_encode($inversion['chord'])}}" style="order: {{$inversion['ranking']}}">
					<i class="fas fa-play-circle mr-2 opacity-4"></i>
					<strong>{!! $inversion['label']['full_shorthand'] !!}</strong>
				</button>
			@endif
		@endforeach
	@endforeach
	</div>
</div>
@endif
<div class="mb-4">
@if($request['has_relevant'])
<label>Other options are probably just inversions of the above.</label>
@endif
@if($request['has_irrelevant'])

	@if(! $request['has_relevant'])
	<label class="">These notes don't make commonly used chords. Here is what we could come up with...</label>
	@endif

	<div class="chords-results d-flex flex-wrap">
	@foreach($request['chords'] as $chord)
		@foreach($chord['inversions'] as $inversion)
			@if($inversion['ranking'] > $request['most_relevant'] + 1)
				<button class="btn btn-chord-additional m-1" 
					href="#{{str_replace('#', '', chordToHumans($inversion['label']['full_shorthand']))}}" 
					data-notes="{{json_encode($inversion['chord'])}}" style="order: {{$inversion['ranking']}}">
					<i class="fas fa-play-circle mr-2 opacity-4"></i>
					<strong>{!! $inversion['label']['full_shorthand'] !!}</strong>
				</button>
			@endif
		@endforeach
	@endforeach
	</div>
@endif
</div>