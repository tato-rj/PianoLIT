@extends('layouts.app')

@push('header')
<style type="text/css">
.note {
	position: relative;
}

.note h1 span {
	font-size: .4em;
	vertical-align: super;
	font-style: italic;
}

.note button {
	border: 0;
	padding: 0;
	background: transparent;
}

.note .overlay {
	width: 100%; 
	height: 100%; 
	position: absolute; 
	top: 0; 
	left: 0; 
	display: none; 
	background: transparent;
}

.note-inactive {
	opacity: .6;
}

.note-inactive div {
	background-color: #b8c2cc;
}

.note-inactive h1 {
	color: #b8c2cc;
	border-color: #b8c2cc !important;
}

.note-active div {
	background-color: #4dc0b5;
}

.note-active h1 {
	color: #4dc0b5;
	border-color: #4dc0b5 !important;
}

button.control:disabled {
	opacity: .4;
	color: white;
}

/* Safari 4.0 - 8.0 */
@-webkit-keyframes loadingAnimation {
  0%   {transform: scale(1);}
  45%  {transform: scale(1.2);}
  70%  {transform: scale(1);}
  100% {transform: scale(1);}
}

/* Standard syntax */
@keyframes loadingAnimation {
  0%   {transform: scale(1);}
  45%  {transform: scale(1.2);}
  70%  {transform: scale(1);}
  100% {transform: scale(1);}
}

.loading {
	animation: loadingAnimation 1s infinite ease-in-out;
	opacity: .4;
}
</style>
@endpush

@section('content')
<div class="container mb-4" id="notes-container">
	<p class="text-center text-grey mb-4">Tap/click on a note to select it</p>
	<div class="row no-gutters justify-content-center mb-4">
		@include('tools.chords.inputs.note', ['note' => 'A'])
		@include('tools.chords.inputs.note', ['note' => 'B'])
		@include('tools.chords.inputs.note', ['note' => 'C'])
		@include('tools.chords.inputs.note', ['note' => 'D'])
		@include('tools.chords.inputs.note', ['note' => 'E'])
		@include('tools.chords.inputs.note', ['note' => 'F'])
		@include('tools.chords.inputs.note', ['note' => 'G'])
	</div>
	<div class="text-center mb-6">
		<button class="btn btn-primary" id="submit-notes">Look it up!</button>
	</div>
</div>
<div class="container mb-6">
	<div class="row">
		<div class="col-12">
			Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
var input = [];

$('.note h1').on('click', function() {
	let $note = $(this).parent();
	$note.toggleClass('note-inactive note-active');
	$note.find('button').toggleAttr('disabled');

	getNotes();
});

$('.note button').on('click', function() {
	let symbol = $(this).attr('data-symbol');
	let accidental = '';
	let accidentals = [];
	let $label = $(this).parent().siblings('h1').find('span');
	let steps = parseInt($label.attr('data-steps'));
	
	if (symbol == '#' && steps < 2) {
		steps += 1;
	} else if (symbol == 'b' && steps > -2) {
		steps -= 1;
	}

	$label.attr('data-steps', steps);

	if (steps == 0)
		$label.text('');

	accidental = steps > 0 ? '#' : 'b';

	for (i=0; i<Math.abs(steps); i++) {
		accidentals.push(accidental);
	}

	$label.text(accidentals.join(''));
	getNotes();
});

function getNotes() {
	let notes = [];

	$('.note-active').each(function() {
		let note = $(this).find('h1').text();
		note = note.replace(/#|_/g, '+');
		note = note.replace(/b|_/g, '-');
		notes.push(note);
	});

	input = notes;

	return notes;
}

$('button#submit-notes').on('click', function() {

	if (input.length < 2) {
		alert('Please select at least two notes');
	} else {
		$(this).prop('disabled', true);
		$(this).text('We\'re working on it...');

		animate();
		submit();
	}

});

function animate() {
	let $notes = $('.note');

	for (var i = 0; i<$notes.length; i++) {
		let $note = $($notes[i]);
		$note.find('.overlay').show();
		setTimeout(function() {
			$note.addClass('loading');
		}, i*100);
	}
}

function submit(notes) {
	console.log('Sending: '+input)
	$.get('{{route('tools.chord-finder.analyse')}}', {notes: input}, function(response) {
		$('#notes-container').html(response);
	}).fail(function(response) {
		console.log(response);
	});
}
</script>
@endpush