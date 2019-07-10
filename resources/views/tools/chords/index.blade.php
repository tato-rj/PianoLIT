@extends('layouts.app')

@push('header')
<style type="text/css">
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
</style>
@endpush

@section('content')
<div class="container mt-4 mb-6">
	<div class="row no-gutters justify-content-center">
		@include('tools.chords.inputs.note', ['note' => 'A'])
		@include('tools.chords.inputs.note', ['note' => 'B'])
		@include('tools.chords.inputs.note', ['note' => 'C'])
		@include('tools.chords.inputs.note', ['note' => 'D'])
		@include('tools.chords.inputs.note', ['note' => 'E'])
		@include('tools.chords.inputs.note', ['note' => 'F'])
		@include('tools.chords.inputs.note', ['note' => 'G'])
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$('.note h1').on('click', function() {
	let $note = $(this).parent();
	$note.toggleClass('note-inactive note-active');
	$note.find('button').toggleAttr('disabled');

	console.log(getNotes());
});

$('.note button').on('click', function() {
	let symbol = $(this).attr('data-symbol');
	let $label = $(this).parent().siblings('h1').find('span');
	let steps = parseInt($label.attr('data-steps'));
	
	if (symbol == '#' && steps < 2) {
		steps += 1;
	} else if (symbol == 'b' && steps > -2) {
		steps -= 1;
	}

	$label.attr('data-steps', steps);

	if (steps == 0) {
		$label.text('');
	} else if (steps > 0) {
		for (i=0; i<steps; i++) {
			$label.text($label.text() + '#');
		}
	}
	// if ($label.text().length < 2) {
	// 	$label.text(accidental);
	// }
});

function getNotes() {
	let $notes = [];
	$('.note-active').each(function() {
		$note = $(this).find('h1').text();
		$notes.push($note);
	});
	return $notes;
}
</script>
@endpush