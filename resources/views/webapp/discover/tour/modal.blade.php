@component('components.modal', ['id' => 'tour-modal', 'headerNoborder' => true, 'headerBg' => 'primary', 'closeColor' => 'white', 'footerRaw' => 'next', 'bodyNoPadding' => true])
@slot('titleRaw')
<div class="text-center text-white px-3">
	<p class="mb-1"><small>QUESTION <span id="question-iteration"></span> OF <span id="questions-count"></span></small></p>
	<div class="mb-4" id="dots" style="font-size:50%">
		@fa(['icon' => 'circle', 'mr' => 1, 'ml' => 1, 'size' => 'xs', 'color' => 'white'])
		@fa(['icon' => 'circle', 'mr' => 1, 'ml' => 1, 'size' => 'xs', 'color' => 'white'])
		@fa(['icon' => 'circle', 'mr' => 1, 'ml' => 1, 'size' => 'xs', 'color' => 'white'])
		@fa(['icon' => 'circle', 'mr' => 1, 'ml' => 1, 'size' => 'xs', 'color' => 'white'])
	</div>
	<div id="questions">
		<h5 class="mb-4">How long have you been playing piano?</h5>
		<h5 class="mb-4">How are your musical reading skills?</h5>
		<h5 class="mb-4">Which mood defines you most?</h5>
		<h5 class="mb-4">If you were a composer you would be:</h5>
	</div>
</div>
@endslot

@slot('body')
<div id="options">
@include('webapp.discover.tour.options', ['options' => [
	'Just started',
	'Between one and three years',
	'More than three years',
	'More than eight years']])

@include('webapp.discover.tour.options', ['options' => [
	'Can\'t really read either clef',
	'May be able to read treble or bass clef',
	'Can red treble and bass clefs together',
	'Fully understand all musical notation']])

@include('webapp.discover.tour.options', ['options' => [
	'Playful',
	'Dreamy',
	'Elegant',
	'Crazy',
	'Melancholic',
	'Passionate']])

@include('webapp.discover.tour.options', ['options' => [
	'Mozart',
	'Bach',
	'Beethoven',
	'Chopin',
	'Debussy',
	'Bartók']])
</div>
@endslot

@slot('footerRaw')
<button class="btn btn-block btn-green py-4 rounded-bottom" id="next" style="border-radius: 0">NEXT</button>
@endslot

@endcomponent