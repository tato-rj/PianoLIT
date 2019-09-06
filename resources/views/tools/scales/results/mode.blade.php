<div class="tab-pane fade {{$loop->first ? 'show active' : null}}" id="pills-{{str_slug($mode)}}" role="tabpanel" aria-labelledby="pills-{{str_slug($mode)}}-tab">
	<div class="row mb-4">
		@if($mode == 'melodic')
		<div class="col-lg-8 col-md-9 col-12 mx-auto text-center">
			<div class="alert alert-warning d-inline-block">
				<i class="fas fa-exclamation-circle mr-2"></i>
				In this mode, the <strong>{{noteToHumans($notes[5])}} and {{noteToHumans($notes[6])}}</strong> return to <strong>{{noteToHumans($scale['notes']['natural'][5])}} and {{noteToHumans($scale['notes']['natural'][6])}}</strong> on the way down
			</div>
		</div>
		@endif
		<div class="col-12 text-center">
			<p class="text-grey mb-1">The notes in this scale are</p>
			<div class="d-flex flex-wrap justify-content-center" id="notes-{{str_slug($mode)}}">
				@php($octave = 3)
				@foreach($notes as $index => $note)
					@if(! $loop->first && in_array(noteToHumans($note), ['Cbb', 'Cb', 'C', 'C#', 'C##']))
						@php($octave = 4)
					@endif
					<button class="btn btn-teal-outline play-note btn-xl m-1" data-name="{{noteToMachine($note)}}" data-octave="{{$octave}}"><strong>{{noteToHumans($note)}}</strong></button>
				@endforeach
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12 mb-3 text-center" id="keyboard-{{str_slug($mode)}}">
			@include('components.piano.keyboard', [
				'centered' => true,
				'octaves' => [
					3 => [],
					4 => []
				]
			])
			<div id="fingering-{{str_slug($mode)}}" class="mt-2 fingering-container" style="display: none;">
				<div class="mb-1"><small class="label"></small></div>
				<div class="d-flex flex-wrap justify-content-center content">
				</div>
			</div>
		</div>
		<div class="col-lg-8 col-md-10 col-12 mx-auto">
			<div class="row">
				<div class="col-6">
					<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
						data-target="#keyboard-{{str_slug($mode)}}" 
						data-notes-target="#notes-{{str_slug($mode)}}" 
						data-fingering-target="#fingering-{{str_slug($mode)}}"
						data-label="LEFT HAND FINGERING" 
						data-fingering="{{json_encode($scale['lh'])}}" 
						data-notes="{{json_encode($notes)}}">
						<div class="text-muted mb-2"><small><strong>PLAY LEFT HAND</strong></small></div>
						<i class="fas opacity-6 text-grey fa-hand-paper fa-flip-horizontal fa-8x"></i>
					</button>
				</div>
				<div class="col-6">
					<button class="btn btn-light btn-block play-notes pb-4 pt-3" 
						data-target="#keyboard-{{str_slug($mode)}}" 
						data-notes-target="#notes-{{str_slug($mode)}}" 
						data-fingering-target="#fingering-{{str_slug($mode)}}"
						data-label="RIGHT HAND FINGERING" 
						data-fingering="{{json_encode($scale['rh'])}}" 
						data-notes="{{json_encode($notes)}}">
						<div class="text-muted mb-2"><small><strong>PLAY RIGHT HAND</strong></small></div>
						<i class="fas opacity-6 text-grey fa-hand-paper fa-8x"></i>
					</button>
				</div>
			</div>
		</div>	
	</div>
</div>