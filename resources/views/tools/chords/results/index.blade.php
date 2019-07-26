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
		<div id="info-container">
			@foreach($request['chords'] as $chord)
				@foreach($chord['inversions'] as $inversion)
				  <div class="chord-info" style="display: none;" id="{{str_replace('#', '', chordToHumans($inversion['label']['full_shorthand']))}}">
				  	<div class="d-flex mb-4">
						<div class="bg-light rounded px-4 py-3" style="max-width: 200px; width: 100%;">
							<label><small><strong>THIS CHORD HAS</strong></small></label>
							<ul class="list-flat">
								@foreach($inversion['intervals'] as $interval)
								<li>{{chordToHumans($inversion['chord'][0])}} to {{chordToHumans($inversion['chord'][$loop->iteration])}}: <strong>{{$interval['name']}}</strong></li>
								@endforeach
							</ul>
						</div>

				  		<div class="px-4 py-3">
				  			<p>In order to figure out the name of the chord, we consider only the <u>intervals between the root (the first note) and each of the other notes</u>. For example, here the root <strong>{{chordToHumans($inversion['chord'][0])}}</strong> forms a <strong>{{$inversion['intervals'][0]['name']}}</strong> with the second note <strong>{{chordToHumans($inversion['chord'][1])}}</strong>, and so on.</p>
				  			<p>Once each interval has been named, we first need to determine if the chord is major, minor, diminished or augmented. To do that, all we need is the <strong>3<sup>rd</sup></strong> and the <strong>5<sup>th</sup></strong> (learn more about this <a href="#" target="_blank">here</a>). The next step is to check if there is a <strong>7<sup>th</sup></strong>.</p>
				  		</div>
				  	</div>
				  </div>
				@endforeach
			@endforeach
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
	<div class="col-12 text-center mt-2">
		<a href="{{$json}}" target="_blank" class="btn btn-link btn-xs">[See JSON response]</a>
	</div>
</div>