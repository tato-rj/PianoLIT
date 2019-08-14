<div class="row">
	<div class="col-lg-6 col-12 mb-4">
		@include('tools.chords.results.chords')
	</div>
	<div class="col-lg-6 col-12 mb-4">
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
				  <div class="chord-info" style="display: none;" id="{{$inversion['id']}}">
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
							@include('tools.chords.results.labels.root')
							@include('tools.chords.results.labels.type')
							@include('tools.chords.results.labels.sus')
							@include('tools.chords.results.labels.seventh')
							@include('tools.chords.results.labels.others')
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