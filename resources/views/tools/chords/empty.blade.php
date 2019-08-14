<div class="container" id="notes-container">
	<p class="text-center text-grey">Tap/click on a note to select it</p>
	<div class="row position-relative no-gutters justify-content-center mb-4">
		<div class="input-overlay"></div>
		@include('tools.chords.inputs.note', ['note' => 'A', 'octave' => 3])
		@include('tools.chords.inputs.note', ['note' => 'B', 'octave' => 3])
		@include('tools.chords.inputs.note', ['note' => 'C', 'octave' => 4])
		@include('tools.chords.inputs.note', ['note' => 'D', 'octave' => 4])
		@include('tools.chords.inputs.note', ['note' => 'E', 'octave' => 4])
		@include('tools.chords.inputs.note', ['note' => 'F', 'octave' => 4])
		@include('tools.chords.inputs.note', ['note' => 'G', 'octave' => 4])
	</div>
	<p class="text-center text-grey">Or use the keyboard to select the notes</p>
	<div class="row position-relative mb-4">
		<div class="input-overlay"></div>
		<div class="col-12 keyboard-input">
			@include('components.piano.keyboard', [
				'centered' => true,
				'octaves' => [
					3 => [],
					4 => []
				]
			])
		</div>
	</div>
	<div class="row position-relative mb-4" id="options-container" style="display: none;">
		<div class="input-overlay"></div>
		<div class="col-12">
			<div class="rounded bg-light px-5 py-4 text-center">
				<p class="text-muted m-0">Some notes on the piano have more than one name, we call these <strong>enharmonic</strong> notes.</p>
				<div id="options-buttons"></div>
				<p class="text-muted m-0">Please select above which of the enharmonic notes you want to use.</p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12 text-center mb-4">
			<p class="text-center text-grey">All set?</p>
			<button class="btn btn-primary btn-wide" id="submit-notes" data-text="Look up the chords!">Look up the chords!</button>
		</div>
		<div class="col-12 mb-6">
			<p>This tool will not only show you the name of the possible chords based on the notes you put in, but also help you understand how we do this! If you are a beginner this process may seem a bit overwhelming at first but don't worry, it will soon be quite simple.</p>
			<p>Just put the notes you have in the inputs above and we'll show you all the chords we can make with those notes. On the next screen, as you select each chord you will see detailed description about how we figured out its name.</p>
		</div>
	</div>
</div>
<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>