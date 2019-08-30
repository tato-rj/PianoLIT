<ul class="nav justify-content-center nav-pills mb-5" id="pills-tab" role="tablist">
	@foreach($arpeggio['positions'] as $position)
	<li class="nav-item">
		<a class="nav-link {{$loop->first ? 'active' : null}}" id="pills-{{str_slug($position['name'])}}-tab" data-toggle="pill" href="#pills-{{str_slug($position['name'])}}" role="tab" aria-controls="pills-home" aria-selected="true">{{$position['name']}}</a>
	</li>
	@endforeach
</ul>

<div class="tab-content" id="pills-tabContent">
	@foreach($arpeggio['positions'] as $position)
		@include('tools.arpeggios.results.position')
	@endforeach
</div>
{{-- <div class="row">
	<div class="col-12">
		<div id="info-alert" class="rounded text-grey bg-light px-5 py-4 d-flex flex-center mb-4">
			<h4 class="my-4 text-center">Select a chord above to learn more about it!</h4>
		</div>
		<div id="info-container">
			@foreach($request['chords'] as $chord)
				@foreach($chord['inversions'] as $inversion)
				  <div class="chord-info" style="display: none;" id="{{$inversion['id']}}">
				  	<div class="d-flex">
						<div class="mb-4 px-2">
							<label class="alert-grey rounded-top py-2 px-4 text-center w-100 m-0 text-nowrap"><small><strong>THIS CHORD HAS</strong></small></label>
							<ul class="list-flat">
								@foreach($inversion['intervals'] as $interval)
								<li class="py-2 px-4 text-nowrap {{$loop->odd ? 'alert-orange' : 'alert-yellow'}} {{$loop->last ? 'rounded-bottom' : null}}">
									{{chordToHumans($inversion['chord'][0])}} to {{chordToHumans($inversion['chord'][$loop->iteration])}}: <strong>{{$interval['name']}}</strong>
								</li>
								@endforeach
							</ul>
						</div>

						@include('tools.chords.results.labels.accordion', ['index' => $loop->iteration])
				  	</div>
				  </div>
				@endforeach
			@endforeach
		</div>
	</div>
</div> --}}
<div class="row my-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>