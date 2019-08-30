<div class="tab-pane fade show active" id="pills-{{str_slug($position['name'])}}" role="tabpanel" aria-labelledby="pills-{{str_slug($position['name'])}}-tab">
	<div class="row mb-4">
		<div class="col-12 text-center">
			<p class="text-grey mb-1">The notes in this arpeggio are</p>
			<div class="d-flex flex-wrap justify-content-center">
				@php($octave = 3)
				@foreach($position['notes'] as $index => $note)
					@if(! $loop->first && in_array(noteToHumans($note), ['B#', 'C', 'C#']))
						@php($octave = 4)
					@endif
					<button class="btn btn-light btn-xl m-1 play-note shadow-sm" data-name="{{noteToHumans($note)}}" data-octave="{{$octave}}"><strong>{{noteToHumans($note)}}</strong></button>
				@endforeach
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12 mb-3 text-center" id="keyboard-{{str_slug($position['name'])}}">
			@include('components.piano.keyboard', [
				'centered' => true,
				'octaves' => [
					3 => [],
					4 => []
				]
			])
			<div id="fingering-{{str_slug($position['name'])}}" class="mt-2 fingering-container" style="display: none;">
				<div class="mb-1"><small class="label"></small></div>
				<div class="d-flex flex-wrap justify-content-center content">
				</div>
			</div>
		</div>
		<div class="col-lg-8 col-md-10 col-12 mx-auto">
			<div class="row">
				<div class="col-6">
					<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
						data-target="#keyboard-{{str_slug($position['name'])}}" 
						data-fingering-target="#fingering-{{str_slug($position['name'])}}"
						data-label="LEFT HAND FINGERING" 
						data-fingering="{{json_encode($position['lh'])}}" 
						data-notes="{{json_encode($position['notes'])}}">
						<div class="text-muted mb-2"><small><strong>PLAY LEFT HAND</strong></small></div>
						<i class="fas opacity-6 text-grey fa-hand-paper fa-flip-horizontal fa-8x"></i>
					</button>
				</div>
				<div class="col-6">
					<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
						data-target="#keyboard-{{str_slug($position['name'])}}" 
						data-fingering-target="#fingering-{{str_slug($position['name'])}}"
						data-label="RIGHT HAND FINGERING" 
						data-fingering="{{json_encode($position['rh'])}}" 
						data-notes="{{json_encode($position['notes'])}}">
						<div class="text-muted mb-2"><small><strong>PLAY RIGHT HAND</strong></small></div>
						<i class="fas opacity-6 text-grey fa-hand-paper fa-8x"></i>
					</button>
				</div>
			</div>
		</div>	
	</div>
</div>