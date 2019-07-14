<div class="row mb-6">
{{-- 	<div class="col-12 mb-4">
		<label class="text-muted"><small>THE NOTES YOU GAVE US WERE</small></label>
		<h3 class="text-muted"><strong>{{implode(' ', $request['notes'])}}</strong></h3>
	</div> --}}
	<div class="col-8 mb-4">
		@include('tools.chords.results.' . $request['results']['type'])
	</div>
	<div class="col-12 text-center">
			<p class="text-grey">Click the chords above to hear them on the keyboard</p>
			@include('components.piano.keyboard', [
				'octaves' => [
					3 => [],
					4 => []
				]
			])
	</div>
</div>
<div class="row mb-6">
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>