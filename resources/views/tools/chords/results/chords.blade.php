@php($count = 0)
<div class="mb-4">
	<label class=""><strong>MOST LIKELY {{ str_plural('CHORD', $request['chords_count']) }}...</strong></label>
	<div class="chords-results d-flex flex-wrap">
	@foreach($request['chords'] as $index => $chord)
		@foreach($chord['inversions'] as $key => $inversion)
			@if($inversion['ranking'] == $request['most_relevant'])
				@php($count++)
				<button class="m-1 btn btn-chord-main" 
					href="#{{$inversion['id']}}" 
					data-notes="{{json_encode($inversion['chord'])}}" style="order: {{$inversion['ranking']}}">
					<i class="fas fa-play-circle mr-2 opacity-4"></i>
					<strong>{!! $inversion['label']['full_shorthand'] !!}</strong>
				</button>
			@endif
		@endforeach
	@endforeach
	</div>
</div>

@if($request['chords_count'] > $count)
<div class="mb-4">
<label>Less likely chords...</label>
	<div class="chords-results d-flex flex-wrap">
	@foreach($request['chords'] as $chord)
		@foreach($chord['inversions'] as $inversion)
			@if($inversion['ranking'] < $request['most_relevant'])
				<button class="btn btn-chord-additional m-1" 
					href="#{{$inversion['id']}}" 
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