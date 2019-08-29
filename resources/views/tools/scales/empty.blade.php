<div class="container mb-4" id="key-container">
	<p class="text-center text-grey">Tap/click on a note to select it</p>
	<div class="row position-relative no-gutters justify-content-center mb-4">
		<div class="input-overlay"></div>
		@include('tools.chords.inputs.note', ['note' => 'A'])
		@include('tools.chords.inputs.note', ['note' => 'B'])
		@include('tools.chords.inputs.note', ['note' => 'C'])
		@include('tools.chords.inputs.note', ['note' => 'D'])
		@include('tools.chords.inputs.note', ['note' => 'E'])
		@include('tools.chords.inputs.note', ['note' => 'F'])
		@include('tools.chords.inputs.note', ['note' => 'G'])
	</div>
	<div class="row position-relative mb-4 animated" id="key-type-options" style="display: none;">
		<div class="input-overlay"></div>
		<div class="col-12">
			<div class="rounded bg-light px-5 py-4 text-center">
				<div class="bg-grey text-white px-3 rounded-bottom position-absolute" style="top: 0; left: 50%; transform: translateX(-50%);"><small><strong>KEY TYPE</strong></small></div>
				<p class="text-muted my-2">What type of key is it?</p>
				<div>
					<div class="btn-group">
						<button class="btn btn-outline-secondary font-weight-bold" data-name="" type="button">Major</button>
						<button class="btn btn-outline-secondary font-weight-bold" data-name="m" type="button">Minor</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12 text-center mb-4">
			<p class="text-center text-grey">All set?</p>
			<button class="btn btn-primary btn-wide" id="submit-key" data-text="Look up the scales and arpeggios!">Look up the scales and arpeggios!</button>
		</div>
		<div class="col-12 mb-6">
			<p>This tool will not only show you the name of the possible chords based on the notes you put in, but also help you understand how we do this! If you are a beginner this process may seem a bit overwhelming at first but don't worry, it will soon be quite simple.</p>
			<p>Just put the notes you have in the inputs above and we'll show you all the chords we can make with those notes. On the next screen, as you select each chord you will see detailed description about how we figured out its name.</p>
		</div>
	</div>
</div>