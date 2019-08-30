@extends('layouts.app', [
	'title' => 'Arpeggios Tutor | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Arpeggios Tutor',
		'description' => 'All you need to know about the main arpeggios at any time',
		'thumbnail' => asset('images/misc/thumbnails/arpeggios.jpg'),
		'created_at' => carbon('29-08-2019'),
		'updated_at' => carbon('29-08-2019')
	]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
</style>
@endpush

@section('content')

@include('components.title', [
	'version' => '1.0',
	'title' => 'Arpeggios Tutor', 
	'subtitle' => 'Select below the key you need and we\'ll show the notes and fingering for each hand'])

@include('tools.scales.empty')

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('tools.chords.error')
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>

<script type="text/javascript">
//////////////////////
// NOTE INPUT CLICK //
//////////////////////
$('.note h1').on('click', function() {
	let $note = $(this).parent();
    resetNotes();
    selectNote($note);
    showOptions();
});

///////////////////////////////////
// NOTE PLUS/MINUS BUTTONS CLICK //
///////////////////////////////////
$('.note button').on('click', function() {
	let symbol = $(this).attr('data-symbol');
	let accidental = '';
	let accidentals = [];
    let $note = $(this).parent().parent();
	let $label = $(this).parent().siblings('h1').find('span');
	let steps = parseInt($label.attr('data-steps'));
	
	if (symbol == '#' && steps < 1) {
		steps += 1;
	} else if (symbol == 'b' && steps > -1) {
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
    
    let ext = accidentals.join('');

    $note.attr('data-name', $note.attr('data-name')[0] + ext);
});

//////////////////////
// KEY TYPE OPTIONS //
//////////////////////
$(document).on('click', '#key-type-options button', function() {
    let $button = $(this);
    resetOptions($button);
    selectOption($button);

    // let $selected = $('#key-type-options button.btn-teal');
});

////////////////////////
// SUBMIT NOTES CLICK //
////////////////////////
$('button#submit-key').on('click', function() {
    if (missingNote()) {
            alert('Please select the key');
    } else if (missingType()) {
		alert('Please select the type');
        $('#key-type-options').removeClass('bounce').addClass('bounce');
    } else {
		$(this).prop('disabled', true);
		$(this).text('Hang on a sec...');

		animate();
        setTimeout(function() {
            submit();
        }, 800);
	}
});

////////////////
// PLAY NOTES //
////////////////
$(document).on('click', 'button.play-note', function() {
	hideDots();
	resetFingerings();
	playNote($(this));
});

$(document).on('click', 'button.play-notes', function() {
    hideDots();
    let notes = JSON.parse($(this).attr('data-notes'));
    let fingering = JSON.parse($(this).attr('data-fingering'));
    let label = $(this).attr('data-label');
    let target = $(this).attr('data-target');
    let fingeringTarget = $(this).attr('data-fingering-target');
    let noteIndex = firstIndex = 0;

    notes.forEach(function(element, index) {
        let note = element;
        let finger = fingering[index];

        note = noteToHumans(note).toLowerCase();

        let $key = findKey(note, noteIndex, target);

        if ($key == null)
            $key = findKey(note, firstIndex, target);

        noteIndex = $key.hasClass('keyboard-black-key') ? $key.parent().next().index() : $key.index();
        
        if (firstIndex == 0)
            firstIndex = noteIndex;

        console.log('Playing ' + note + ' at index ' + noteIndex);
        
        resetFingerings();

        setTimeout(function() {
            press($key, 150, false);
            showFingering(finger, label, fingeringTarget);
            highlight($key);
        }, 300 * index);
    });
});

///////////////
// FUNCTIONS //
///////////////
function resetFingerings() {
	$('.fingering-container').hide();
	$('.fingering-container small.label').text('');
	$('.fingering-container > div.content').html('');
}

function showFingering(finger, label, container) {
	$(container).find('small.label').text(label);
	$(container).find('> div.content').append('<h4 class="mx-2 my-0 text-muted"><strong>'+finger+'</strong></h4>');
	$(container).show();
}

function showError(response) {
    $('#modal-error .modal-body div#error-report').text(response);
    $('#modal-error').modal('show');

	$('#modal-error').on('hide.bs.modal', function() {
	    reload();
	});
}

function playNote($note) {
	console.log('Playing ' + $note.attr('data-name'));
    play($note.attr('data-name'), $note.attr('data-octave'), 500);
}

function reload() {
    $('button#submit-key').text($('button#submit-key').attr('data-text')).prop('disabled', false);
    $('.input-overlay').hide();
}

function animate() {
    $('.input-overlay').show();
}

function submit() {
	let key = getInput();
	console.log('Sending: ' + key);

	$.get('{{route('tools.arpeggios.generate')}}', {key: key}, function(response) {
		$('#key-container').html(response);
        $('html,body').scrollTop(0);
	}).fail(function(response) {
        showError(response.responseJSON.message);
	});
}

function getInput() {
	let name = noteToMachine($('.note-active').attr('data-name'));
	let type = $('#key-type-options .btn-teal').attr('data-name');

	return name + type;
}

function noteToHumans(note) {
    return ucfirst(note.replace('+', '#').replace('+', '#').replace('-', 'b').replace('-', 'b').replace('2', ''));
}

function noteToMachine(note) {
    let letter = ucfirst(note[0]);

    return letter + note.substring(1).replace('#', '+').replace('#', '+').replace('b', '-').replace('b', '-');    
}

function resetNotes() {
    let $notes = $('.note');
    
	$notes.removeClass('note-active').addClass('note-inactive opacity-4');
	$notes.find('button').prop('disabled', true);
}

function selectNote($note) {
	$note.toggleClass('note-inactive note-active opacity-4');
	$note.find('button').toggleAttr('disabled');
}

function resetOptions() {
    $('#key-type-options button').removeClass('btn-teal').addClass('btn-outline-secondary');
}

function selectOption($button) {
    $button.removeClass('btn-outline-secondary').addClass('btn-teal');
}

function showOptions() {
	$('#key-type-options').show();
}

function missingNote() {
	return ! $('.note-active').length;
}

function missingType() {
	return ! $('#key-type-options .btn-teal').length;
}

const ucfirst = (s) => {
  if (typeof s !== 'string') return ''
  return s.charAt(0).toUpperCase() + s.slice(1)
}
</script>
@endpush