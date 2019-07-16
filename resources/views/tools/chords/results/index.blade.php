<div class="row mb-6">
	<div class="bg-light rounded col-3 mb-4 px-4 py-3">
		<div class="mb-2 pb-2 border-bottom">
			<label class="text-muted"><small>THE NOTES YOU GAVE US:</small></label>
			<h2 class="text-grey"><strong>{{implode(' ', $request['notes'])}}</strong></h2>
		</div>
		<div>
			<p class="text-muted"><small>By inverting the notes, the possible chords we can make are:</small></p>
			@foreach($request['notes'] as $note)
				<h5 class="text-grey"><strong>{{$loop->iteration}}. {{implode(' ', $request['notes'])}}</strong></h5>
				@php(array_push($request['notes'], array_shift($request['notes'])))
			@endforeach
			<p class="text-muted m-0"><small>Our ears will pick up on chords that have <strong>most structural notes</strong> in them: the 3rd, 5th and depending on the style, the 7th as well. That is why we find some chords to be more relevant than others.</small></p>
		</div>
	</div>
	<div class="col-9 mb-4">
		@include('tools.chords.results.' . $request['results']['type'])
		<div class="">
				<p class="text-muted">Click the chords above to hear them on the keyboard</p>
				@include('components.piano.keyboard', [
					'centered' => false,
					'octaves' => [
						3 => [],
						4 => []
					]
				])
		</div>
	</div>
</div>
<div class="row mb-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>