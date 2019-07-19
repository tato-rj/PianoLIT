<div class="container mb-4" id="notes-container">
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
					4 => [],
					5 => []
				]
			])
		</div>
	</div>
	<div class="text-center mb-6">
		<p class="text-center text-grey">All set?</p>
		<button class="btn btn-primary" id="submit-notes"><i class="fas fa-lightbulb mr-2"></i>Look up the chords!</button>
	</div>
</div>
<div class="container mb-6">
	<div class="row">
		<div class="col-12">
			Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
		</div>
	</div>
</div>