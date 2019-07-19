<div class="row">
	<div class="col-6 mb-4">
		@include('tools.chords.results.' . $request['type'])

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
	<div class="col-12 text-center">
		<div id="reload" class="d-inline-block cursor-pointer lead">
			<strong><i class="fas fa-redo mr-2"></i>Start again</strong>
		</div>
	</div>
</div>