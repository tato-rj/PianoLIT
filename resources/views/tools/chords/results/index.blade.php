<div class="row">
	<div class="col-6 mb-4">
		@include('tools.chords.results.chords')
	</div>
	<div class="col-6 mb-4">
		<p class="text-grey"><i class="fas fa-volume-up mr-2"></i>Click the chords to hear them on the keyboard</p>
		@include('components.piano.keyboard', [
			'centered' => false,
			'octaves' => [
				3 => [],
				4 => []
			]
		])
		<div id="chord-label" class="d-flex justify-content-center"></div>
	</div>
</div>
<div class="row">
	<div class="col-12">
		<div id="info-alert" class="rounded text-grey bg-light px-5 py-4 d-flex flex-center mb-4">
			<h4 class="my-4 text-center">Select a chord above to learn more about it!</h4>
		</div>
		<div id="info-container">
			@foreach($request['chords'] as $chord)
				@foreach($chord['inversions'] as $inversion)
				  <div class="chord-info" style="display: none;" id="{{str_replace('#', '', chordToHumans($inversion['label']['full_shorthand']))}}">
				  	<div class="d-flex">
						<div class="mb-4">
							<label class="alert-grey rounded-top py-2 px-4 text-center w-100 m-0 text-nowrap"><small><strong>THIS CHORD HAS</strong></small></label>
							<ul class="list-flat">
								@foreach($inversion['intervals'] as $interval)
								<li class="py-2 px-4 text-nowrap {{$loop->odd ? 'alert-orange' : 'alert-yellow'}} {{$loop->last ? 'rounded-bottom' : null}}">
									{{chordToHumans($inversion['chord'][0])}} to {{chordToHumans($inversion['chord'][$loop->iteration])}}: <strong>{{$interval['name']}}</strong>
								</li>
								@endforeach
							</ul>
						</div>

				  		<div class="px-4">
				  			<span class="badge alert-red m-0">STEP 1: INTERVALS</span>
				  			<div class="p-2 text-muted">
					  			<p>In order to figure out the name of the chord, we consider only the <u>intervals between the root (the first note) and each of the other notes</u>. Here, the root {{chordToHumans($inversion['chord'][0])}} forms a {{$inversion['intervals'][0]['name']}} with the second note {{chordToHumans($inversion['chord'][1])}}, and so on.</p>
							</div>
				  			<span class="badge alert-red m-0">STEP 2: 3rd AND 5th</span>
				  			<div class="p-2 text-muted">
					  			<p>Once each interval has been named, we first need to determine if the chord is major, minor, diminished or augmented. To do that, all we need is the <strong>3rd</strong> and the <strong>5th</strong>. Learn more about this <a href="#" target="_blank">here</a>.</p>
							</div>
				  			<span class="badge alert-red m-0">STEP 3: ADD OR SUS</span>
				  			<div class="p-2 text-muted">
				  				<p>Once we figure out what type of chord this is based on the 3rd and the 5th, we can look for other intervals. If there is a 2nd and/or a 4th, we'll indicate that by saying this is an <strong>add</strong> or <strong>sus</strong> chord. Learn more about this <a href="#" target="_blank">here</a>.</p>
							</div>
				  			<span class="badge alert-red m-0">STEP 4: THE 7th</span>
				  			<div class="p-2 text-muted">
				  				<p>Next, we'll check if there is a <strong>7th</strong>. If yes, we will add the 7th to the chord according to the corresponding interval. Learn more about this <a href="#" target="_blank">here</a>.</p>
							</div>
				  			<span class="badge alert-red m-0">STEP 5: OTHER INTERVALS</span>
				  			<div class="p-2 text-muted">
				  				<p>Finally, let's look for any other intervals, such as a <strong>6th</strong>, a <strong>9th</strong>, an <strong>11th</strong>, etc. For each one, we'll add that note the chord according to the corresponding interval. Learn more about this <a href="#" target="_blank">here</a>.</p>
				  			</div>
				  		</div>
				  	</div>
				  </div>
				@endforeach
			@endforeach
		</div>
	</div>
</div>
<div class="row my-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
	@if(app()->isLocal() || request()->has('dev'))
	<div class="col-12 text-center mt-2">
		<a href="{{$json}}" target="_blank" class="btn btn-link btn-xs">[See JSON response]</a>
	</div>
	@endif
</div>