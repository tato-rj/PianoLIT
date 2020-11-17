@component('components.modal', [
	'id' => 'tour-modal',
	'options' => [
		'header' => [
			'background' => 'primary',
			'raw' => true,
			'close' => ['color' => 'white', 'position' => 'absolute-top-right']
		],
		'body' => ['padding' => 0],
		'footer' => ['raw' => true]
	]
])
@slot('header')
<div class="text-center text-white px-3">
	<p class="mb-1"><small>QUESTION <span id="question-iteration"></span> OF <span id="questions-count"></span></small></p>
	<div class="mb-4" id="dots" style="font-size:50%">
		@include('components.carousel.dots', ['color' => 'white'])
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
	'Just started' => '0',
	'Between one and three years' => '1',
	'More than three years' => '2',
	'More than eight years' => '3']])

@include('webapp.discover.tour.options', ['options' => [
	'Can\'t really read either clef' => '0',
	'May be able to read treble or bass clef' => '1',
	'Can red treble and bass clefs together' => '2',
	'Fully understand all musical notation' => '3']])

@include('webapp.discover.tour.options', ['options' => [
	'Playful' => 'playful',
	'Dreamy' => 'dreamy',
	'Elegant' => 'elegant',
	'Crazy' => 'crazy',
	'Melancholic' => 'melancholic',
	'Passionate' => 'passionate']])

@include('webapp.discover.tour.options', ['options' => [
	'Mozart' => 'classical',
	'Bach' => 'baroque',
	'Beethoven' => 'romantic',
	'Chopin' => 'romantic',
	'Debussy' => 'romantic',
	'Bartók' => 'modern']])
</div>
@endslot

@slot('footer')
<button class="btn btn-block btn-green py-4 rounded-bottom" id="next" style="border-radius: 0">NEXT</button>
@endslot

@endcomponent