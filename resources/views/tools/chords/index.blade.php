@extends('layouts.app')

@push('header')
<style type="text/css">

sup.extension {
    margin-left: 4px;
}
/*START WORKERS*/
.place_balk_workers {
	width: 437px;
	position: relative;
	margin: 0 auto;
}

.place_balk_workers .balk_workers{
    width: 437px;
    height: 213px;
    position: relative;
    background-image: url("/images/animations/workers.svg");
    background-repeat: no-repeat;
    background-position: center bottom;
    background-color: transparent;
    background-size: 100%;
    z-index: 3;
}

.place_balk_workers .balk {
    width: 60px;
    height: 23px;
    background-image: url("/images/animations/balk.svg");
    background-repeat: no-repeat;
    background-position: center bottom;
    background-color: transparent;
    background-size: 100%;
    position: absolute;
    top: 92px;
    left: 194px;
}

.place_balk_workers .saw{
    width: 325px;
    height: 63px;
    background-image: url("/images/animations/saw.svg");
    background-repeat: no-repeat;
    background-position: center bottom;
    background-color: transparent;
    background-size: 100%;
    position: absolute;
    left: 53px;
    top: 68px;
    z-index: 2;
    -moz-animation: 1.0s ease 0s normal none infinite saw;
    -moz-transform-origin: 85% 65%;
    -webkit-animation:saw 1.0s infinite ease-in-out;
    -webkit-transform-origin: 85% 65%;
    -o-animation: 1.0s ease 0s normal none infinite saw;
    -o-animation:saw 1.0s infinite ease-in-out;
    -o-transform-origin: 85% 65%;
    -ms-animation: 1.0s ease 0s normal none infinite saw;
    -ms-animation:saw 1.0s infinite ease-in-out;
    -ms-transform-origin: 85% 65%;
    animation: 1.0s ease 0s normal none infinite saw;
    animation:saw 1.0s infinite ease-in-out;
    transform-origin: 85% 65%;
}

@-moz-keyframes saw {
    0%{left:53px}
    50%{left:72px}
    100%{left:53px}
}
@-webkit-keyframes saw {
    0%{left:53px}
    50%{left:72px}
    100%{left:53px}
}
@-o-keyframes saw {
    0%{left:53px}
    50%{left:72px}
    100%{left:53px}
}
@-ms-keyframes saw {
    0%{left:53px}
    50%{left:72px}
    100%{left:53px}
}
@keyframes saw {
    0%{left:53px}
    50%{left:72px}
    100%{left:53px}
}

@-moz-keyframes saw_mob {
    0%{left:47px}
    50%{left:33px}
    100%{left:47px}
}
@-webkit-keyframes saw_mob {
    0%{left:47px}
    50%{left:33px}
    100%{left:47px}
}
@-o-keyframes saw_mob {
    0%{left:47px}
    50%{left:33px}
    100%{left:47px}
}
@-ms-keyframes saw_mob {
    0%{left:47px}
    50%{left:33px}
    100%{left:47px}
}
@keyframes saw_mob {
    0%{left:47px}
    50%{left:33px}
    100%{left:47px}
}

/* Start media ( max = 468px ) */
@media (max-width: 468px) {
    .place_balk_workers {
        width: 290px;
        height: 150px;
        margin: 40px auto -10px;
    }
    .place_balk_workers .balk_workers {
        width: 290px;
        height: 150px;
    }
    .place_balk_workers .balk {
        width: 41px;
        height: 15px;
        top: 70px;
        left: 128px;
    }
    .place_balk_workers .saw {
        width: 215px;
        height: 36px;
        left: 37px;
        top: 59px;
        -moz-animation: 1.0s ease 0s normal none infinite saw_mob;
        -moz-transform-origin: 85% 65%;
        -webkit-animation: saw_mob 1.0s infinite ease-in-out;
        -webkit-transform-origin: 85% 65%;
        -o-animation: 1.0s ease 0s normal none infinite saw_mob;
        -o-animation: saw_mob 1.0s infinite ease-in-out;
        -o-transform-origin: 85% 65%;
        -ms-animation: 1.0s ease 0s normal none infinite saw_mob;
        -ms-animation: saw_mob 1.0s infinite ease-in-out;
        -ms-transform-origin: 85% 65%;
        animation: 1.0s ease 0s normal none infinite saw_mob;
        animation: saw_mob 1.0s infinite ease-in-out;
        transform-origin: 85% 65%;
    }

}

/*END OF WORKERS*/

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

.input-overlay {
	width: 100%; 
	height: 100%; 
	position: absolute; 
	top: 0; 
	left: 0; 
	display: none; 
	background: rgba(255,255,255,0.5);
    z-index: 100;
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
	animation-name: loadingAnimation;
    animation-iteration-count: infinite;
    animation-timing-function: ease-in-out;
	opacity: .6;
}

.btn-chord-main {
    font-size: 1.5em;
    background-color: #2fe4581f;
    color: #20a23ed1;
    transition: .2s;
}

.btn-chord-main:hover {
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    color: #20a23ed1;
}

.btn-chord-main:active {
    background-color: #2fe45861;
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

.btn-chord-main:focus {
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

.btn-chord-main.btn-chord-selected {
    background-color: #2fe4584d;
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

.btn-chord-additional.btn-chord-selected {
    color: #544c00ab;
    background-color: #f2da008c;
}

.btn-chord-additional {
    background-color: #f2da004a;
    color: #544c00ab;
    transition: .2s;
}

.btn-chord-additional:hover {
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    color: #544c00ab;
}

.btn-chord-additional:active {
    background-color: #f2da008c;
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

.btn-chord-additional:focus {
    -webkit-box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}

#chord-label > div {
    margin: .5rem!important;
    font-size: 1.6em;
    color: #6c757d!important;
    font-weight: bold;
    text-transform: capitalize;
}
</style>
@endpush

@section('content')
<div class="mb-4 text-center position-relative">
    @include('components.overlays.loading')
	<h3>Chord Finder</h3>
    <div id="subtitle" class="text-grey">
        <div class="">Just tell us the notes and we'll show the most likely chords you can make with them</div>
    </div>
</div>
@if(app()->isLocal() || request()->has('dev'))
    @if(! empty($request))
    <div class="container mb-4" id="notes-container">
        @include('tools.chords.results.index')
    </div>
    @else
        @include('tools.chords.empty')
    @endif
@else
<div class="my-6">
	@include('components.animations.workers')
	<h3 class="text-grey text-center my-4">Coming up soon!</h3>
</div>
@endif

@include('tools.chords.error')
@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>

<script type="text/javascript">
$(document).on('click', '#reload', function() {
    window.location = window.location.href.split("?")[0];
});
</script>

<script type="text/javascript">
$(document).on('click', '.chords-results button', function() {
    if (notPlaying) {
        hideDots();
        resetLabel();
        $('.chords-results button').removeClass('btn-chord-selected');
        $(this).addClass('btn-chord-selected');
        let notes = JSON.parse($(this).attr('data-notes'));
        let info = $(this).attr('href');
        let noteIndex = 0;
        let chord = [];

        $('.chord-info').hide();
        $('#info-alert').remove();
        $(info).fadeIn('fast');

        notes.forEach(function(element, index) {
            let note = element.replace('+', '#').replace('-', 'b').replace('2', '');
            let $key = findKey(note, noteIndex);
            chord.push(note + $key.attr('data-octave'));
            noteIndex = $key.hasClass('keyboard-black-key') ? $key.parent().next().index() : $key.index();

            setTimeout(function() {
                press($key, 150, false);
                highlight($key);
                updateLabel(note);
            }, 200 * index);
        });

        setTimeout(function() {
            piano.triggerAttackRelease(chord, "1n");
        }, (notes.length + 1) * 200);
    }
});

function updateLabel(note) {
    $label = $('#chord-label');
    $label.append('<div>'+note+'</div>');
}

function resetLabel() {
    $('#chord-label').html('');
}

</script>
<script type="text/javascript">
var input = exclude = include = [];

$(document).on('click', '#options-container button', function() {
    let $button = $(this);

    resetOptions($button);

    $button.removeClass('btn-outline-secondary').addClass('btn-green');

    $('#options-container button').each(function() {
        let $sibling = $(this).siblings('button').first();
        if ($(this).hasClass('btn-green')) {
            exclude.push($sibling.attr('data-name'));
            include.push($(this).attr('data-name'));
        }
    });

    input.forEach(function(item, key) {
        if (exclude.includes(item))
            input.splice(key, 1);
    });

    include.forEach(function(item, key) {
        if (! input.includes(item))
            input.push(item);
    });
    
    console.log(input);
});

$(document).on('click', '#reset-options', function() {
    resetOptions();
    console.log(input);
});

function showOptions(notes) {
    $('#options-buttons').html('');
    let singleNotes = ['D', 'G', 'A'];

    for (var i=0; i<notes.length-1; i++) {
        let first = notes[i];
        let second = notes[i+1];

        if (! singleNotes.includes(notes[i]) && $('button[data-name="'+first+'"]').length == 0) {
            if (first && second) {
                let firstToHumans = first.replace('+', '#');
                    firstToHumans = firstToHumans.replace('-', 'b');
                let secondToHumans = second.replace('+', '#');
                    secondToHumans = secondToHumans.replace('-', 'b');

                let html = `<div class="btn-group m-2">
                                <button class="btn btn-outline-secondary font-weight-bold" data-name="`+first+`" type="button">`+firstToHumans+`</button>
                                <button class="btn btn-outline-secondary font-weight-bold" data-name="`+second+`" type="button">`+secondToHumans+`</button>
                            </div>`;

                $('#options-buttons').append(html);
            }
        }
    }

    if ($('#options-buttons > div').length > 0) {
        $('#options-container').show();
    } else {
        $('#options-container').hide();
    }
}

function resetOptions(element = null) {
    include = [];
    exclude = [];

    if (element) {
        element.siblings('button').first().removeClass('btn-green').addClass('btn-outline-secondary');
    } else {
        $('#options-container button').removeClass('btn-green').addClass('btn-outline-secondary');
    }

    if ($('#reset-options').attr('data-original'))
        input = JSON.parse($('#reset-options').attr('data-original'));
}

function removeOptions() {
    resetOptions();
    showOptions([]);
}

$('.keyboard-input .keyboard-white-key, .keyboard-input .keyboard-white-key').on('click', function(e) {
    let $key = $(e.target);
    reset('.note');
    $key.find(' > .dot').toggle();

    notes = getNotes();

    showOptions(notes);
});

$('.dot').on('click', function() {
    $(this).hide();

    getNotes();
});

$('.note h1').on('click', function() {
	let $note = $(this).parent();
	$note.toggleClass('note-inactive note-active');
	$note.find('button').toggleAttr('disabled');

    reset('.dot');
    removeOptions();
    playNote($note);
    getNotes();
});

$('.note button').on('click', function() {
	let symbol = $(this).attr('data-symbol');
	let accidental = '';
	let accidentals = [];
    let $note = $(this).parent().parent();
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
    
    let ext = accidentals.join('') == '##' ? 'x' : accidentals.join('');

    $note.attr('data-name', $note.attr('data-name')[0] + ext);
    playNote($note);
	getNotes();
});

function reset(elem) {
    let $notes = $(elem);
    
    if (elem == '.dot') {
        $notes.hide();
    } else {
        $notes.removeClass('note-active').addClass('note-inactive');
        $notes.find('button').prop('disabled', true);
    }
}

function playNote($note) {
    if ($note.hasClass('note-active'))
        play($note.attr('data-name'), $note.attr('data-octave'), 500);
}

function getNotes() {
    let $firstKey = $('.dot:visible').first();
	let notes = [];

	$('.note-active').each(function() {
		let note = $(this).find('h1').text();
		note = note.replace(/#|_/g, '+');
		note = note.replace(/b|_/g, '-');
		notes.push(note);
	});

    $('.dot:visible').each(function() {
        let $key = $(this).parent();
        let note = $key.attr('data-name').toUpperCase();

        note = note.replace(/#|_/g, '+');

        if (note == 'C')
            notes.push('B+');

        if (note == 'F')
            notes.push('E+');
        
        notes.push(note);

        if (note == 'B')
            notes.push('C-');

        if (note == 'E')
            notes.push('F-');
        
        if (note.includes('+'))
            notes.push(nextLetter(note[0]) + '-');

    });

	console.log(notes);
    input = notes;
    $('#reset-options').attr('data-original', JSON.stringify(notes));

	return notes;
}

$('button#submit-notes').on('click', function() {

	if (input.length < 3) {
		alert('Please select at least 3 notes');
	} else {
		$(this).prop('disabled', true);
		$(this).text('Hang on a sec...');

		animate();
        setTimeout(function() {
            submit();
        }, 1500);
	}

});

function nextLetter(letter) {
    let next = letter.substring(0, letter.length - 1) + String.fromCharCode(letter.charCodeAt(letter.length - 1) + 1);

    if (next == 'H')
        return 'A';

    return next;
}

function animate() {
    $('.input-overlay').show();
}

function submit() {
	console.log('Sending: '+input)
	$.get('{{route('tools.chord-finder.analyse')}}', {notes: input}, function(response) {
		$('#notes-container').html(response);
        $('html,body').scrollTop(0);
	}).fail(function(response) {
        showError(response.responseJSON.message);
	});
}

function showError(response) {
    $('#modal-error .modal-body div').text(response);
    $('#modal-error').modal('show');
}

$('#modal-error').on('hide.bs.modal', function() {
    reload();
});

function reload() {
    reset('.dot');
    reset('.note');
    $('button#submit-notes').text($('button#submit-notes').attr('data-text')).prop('disabled', false);
    $('.input-overlay').hide();
}

function updateUrl(notes) {
    let query = '?';
    notes.forEach(function(note) {
        query += 'notes[]=' + note.replace('+', 's') + '&';
    });
    if (history.pushState) {
        let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + query;
        window.history.pushState({path:newurl},'',newurl);
    }
}
</script>
@endpush