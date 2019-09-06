<div class="container mb-4" id="key-container">
	@include('tools.technique.input')
	@include('tools.technique.components.key')
	<div class="row">
		@include('tools.technique.components.submit', ['type' => 'scale'])
		<div class="col-12 mb-6">
			<p>This tool will not only show you the name of the possible chords based on the notes you put in, but also help you understand how we do this! If you are a beginner this process may seem a bit overwhelming at first but don't worry, it will soon be quite simple.</p>
			<p>Just put the notes you have in the inputs above and we'll show you all the chords we can make with those notes. On the next screen, as you select each chord you will see detailed description about how we figured out its name.</p>
		</div>
	</div>
</div>