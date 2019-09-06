<div class="row mb-4">
	<div class="col-12 text-center">
		<p class="text-grey mb-1">The notes in this scale are</p>
		<div class="d-flex flex-wrap justify-content-center">
			@php($octave = 3)
			@foreach($scale['notes'][0] as $index => $note)
				@if(! $loop->first && in_array(noteToHumans($note), ['Cbb', 'Cb', 'C', 'C#', 'C##']))
					@php($octave = 4)
				@endif
				<button class="btn btn-teal-outline play-note btn-xl m-1" data-name="{{noteToMachine($note)}}" data-octave="{{$octave}}">
					<strong>{{noteToHumans($note)}}</strong>
				</button>
			@endforeach
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12 mb-3 text-center">
		@include('components.piano.keyboard', [
			'centered' => true,
			'octaves' => [
				3 => [],
				4 => []
			]
		])
		<div id="scale-fingering" class="mt-2" style="display: none;">
			<div class="mb-1"><small class="label"></small></div>
			<div class="d-flex flex-wrap justify-content-center content">
			</div>
		</div>
	</div>
	<div class="col-lg-8 col-md-10 col-12 mx-auto">
		<div class="row">
			<div class="col-6">
				<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
					data-label="LEFT HAND FINGERING" 
					data-fingering="{{json_encode($scale['lh'])}}" 
					data-notes="{{json_encode($scale['notes'][0])}}">
					<div class="text-muted mb-2"><small><strong>PLAY LEFT HAND</strong></small></div>
					<i class="fas opacity-6 text-grey fa-hand-paper fa-flip-horizontal fa-8x"></i>
				</button>
			</div>
			<div class="col-6">
				<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
					data-label="RIGHT HAND FINGERING" 
					data-fingering="{{json_encode($scale['rh'])}}" 
					data-notes="{{json_encode($scale['notes'][0])}}">
					<div class="text-muted mb-2"><small><strong>PLAY RIGHT HAND</strong></small></div>
					<i class="fas opacity-6 text-grey fa-hand-paper fa-8x"></i>
				</button>
			</div>
		</div>
	</div>	
</div>

<div class="row my-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>