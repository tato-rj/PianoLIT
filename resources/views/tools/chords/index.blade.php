@extends('layouts.app', [
    'title' => 'The Ultimate Chord Finder | ' . config('app.name'),
    'shareable' => [
        'keywords' => 'chords,chord finder,music theory,harmony',
        'title' => 'The Ultimate Chord Finder',
        'description' => 'Give us the notes and we\'ll tell you the chords you can make with them. Also learn how this process works with an easy step-by-step guide.',
        'thumbnail' => asset('images/misc/thumbnails/chords.jpg'),
        'created_at' => carbon('10-08-2019'),
        'updated_at' => carbon('20-08-2019')
    ]])

@push('header')
<style type="text/css">
.fadeInUp {
    animation-duration: .2s;
}
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
    @include('components.overlays.loading')

    @include('components.title', [
        'version' => '2.0',
        'title' => 'Chord Finder', 
        'subtitle' => 'Just tell us the notes and we\'ll show the most likely chords you can make with them'])


@if(! empty($request))
<div class="container mb-4" id="notes-container">
    @include('tools.chords.results.index')
</div>
@else
    @include('tools.chords.empty')
@endif

@include('tools.chords.error')
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
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
        let noteIndex = firstIndex = 0;
        let chord = [];

        $('.chord-info').hide();
        $('#info-alert').remove();
        $(info).fadeIn('fast');

        notes.forEach(function(element, index) {
            let note = element;

            note = noteToHumans(note).toLowerCase();

            let $key = findKey(note, noteIndex);

            if ($key == null)
                $key = findKey(note, firstIndex);

            chord.push(note + $key.attr('data-octave'));
            noteIndex = $key.hasClass('keyboard-black-key') ? $key.parent().next().index() : $key.index();
            
            if (firstIndex == 0)
                firstIndex = noteIndex;

            console.log('Playing ' + note + ' at index ' + noteIndex);
            
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
var root = tool = null;

////////////////////////
// ENHARMONIC OPTIONS //
////////////////////////
$(document).on('click', '#options-container button', function() {
    let $button = $(this);
    resetOptions($button);
    $button.removeClass('btn-outline-secondary').addClass('btn-green');

    let $selected = $('#options-container button[data-source="'+$button.attr('data-source')+'"].btn-green');
    let target, source;
    
    target = $selected.attr('data-name');
    source = $selected.attr('data-source');

    notes = [];

    $('#options-container button.btn-green').each(function() {
        notes.push($(this).attr('data-name'));
    });
    
    console.log('You just selected the ' + target);

    console.log('Set of notes: ' + notes);
    
    input = notes;
        
    if (missingEnharmonics() == 0)
        showRootOptions(input);
});

//////////////////
// ROOT OPTIONS //
//////////////////
$(document).on('click', '#root-container button', function() {
    let $button = $(this);

    $('#root-container button').not(this).removeClass('btn-green').addClass('btn-outline-secondary');
    
    if ($button.hasClass('btn-outline-secondary')) {
        root = $button.attr('data-name');
        console.log('You just selected ' + root + ' as the root');
    } else {
        root = null;
        console.log('The root has been erased');
    }

    $button.toggleClass('btn-outline-secondary btn-green');
});

function noteToHumans(note) {
    return ucfirst(note.replace('+', '#').replace('+', '#').replace('-', 'b').replace('-', 'b').replace('2', ''));
}

function noteToMachine(note) {
    let letter = ucfirst(note[0]);

    return letter + note.substring(1).replace('#', '+').replace('#', '+').replace('b', '-').replace('b', '-');    
}

function unique(value, index, self) { 
    return self.indexOf(value) === index;
}

function showRootOptions(notes) {
    let array = notes.filter(unique);
    $('#root-buttons').html('');
    root = null;

    if (array.length > 2) {
        for (var i=0; i<array.length; i++) {
            let html = `<div class="m-2 d-inline-block">
                            <button class="btn btn-outline-secondary font-weight-bold" data-name="`+array[i]+`" type="button">`+noteToHumans(array[i])+`</button>
                        </div>`;

            $('#root-buttons').append(html);
        }
    }
    
    if ($('#root-buttons > div').length > 0) {
        $('#root-container').show();
    } else {
        $('#root-container').hide();
    }
}

function showEnharmonicOptions(notes) {
    $('#options-buttons').html('');

    for (var i=0; i<notes.length; i++) {
        let enharmonics = JSON.parse($('.keyboard-key[data-name="'+noteToHumans(notes[i]).toLowerCase()+'"]').attr('data-names'));

            let html = '<div class="btn-group m-2">';

            enharmonics.forEach(function(enharmonic) {
                html += '<button class="btn btn-outline-secondary font-weight-bold" data-source="'+notes[i]+'" data-name="'+noteToMachine(enharmonic)+'" type="button">'+noteToHumans(enharmonic)+'</button>';
            });
            
            html += '</div>';

            $('#options-buttons').append(html);
        
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
        element.siblings('button').removeClass('btn-green').addClass('btn-outline-secondary');
    } else {
        $('#options-container button').removeClass('btn-green').addClass('btn-outline-secondary');
    }
}

function removeOptions() {
    resetOptions();
    showEnharmonicOptions([]);
    showRootOptions([]);
}

////////////////////
// KEYBOARD CLICK //
////////////////////
$('.keyboard-input .keyboard-white-key, .keyboard-input .keyboard-white-key').on('click', function(e) {
    let $key = $(e.target);
 
    tool = 'keyboard';
 
    reset('.note');
 
    $key.find(' > .dot').toggle();

    notes = getNotes();

    showEnharmonicOptions(notes);

    showRootOptions([]);
});

$('.dot').on('click', function() {
    $(this).hide();
    getNotes();
});

//////////////////////
// NOTE INPUT CLICK //
//////////////////////
$('.note h1').on('click', function() {
	let $note = $(this).parent();
	$note.toggleClass('note-inactive note-active');
	$note.find('button').toggleAttr('disabled');

    tool = 'button';

    reset('.dot');
    removeOptions();
    playNote($note);
    notes = getNotes();
    showRootOptions(notes);
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
	notes = getNotes();
    showRootOptions(notes);
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
	let notes = [];

	$('.note-active').each(function() {
		let note = $(this).find('h1').text();
		note = note.replace(/#|_/g, '+');
		note = note.replace(/b|_/g, '-');
		notes.push(note);
	});

    $('.keyboard-key').each(function() {
        $key = $(this);

        if ($key.find('>.dot:visible').length > 0) {
            let note = $key.attr('data-name').toUpperCase();
            
            notes.push(note.replace(/#|_/g, '+'));
        }
    });

    input = notes;

    $('#reset-options').attr('data-original', JSON.stringify(notes));

	return notes;
}

////////////////////////
// SUBMIT NOTES CLICK //
////////////////////////
$('button#submit-notes').on('click', function() {
    if (missingEnharmonics() > 0) {
            alert('Please select each enharmonic note, you\'re missing ' + missingEnharmonics());
            $('#options-container').removeClass('bounce').addClass('bounce');
    } else if (input.length < 3) {
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

function missingEnharmonics() {
    return $('#options-buttons .btn-group').length - $('#options-buttons .btn-green').length;
}

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
	console.log('Sending: ' + input);
    console.log('Tool used: ' + tool);
    console.log(root ? 'The selected root is ' + root : 'No root was selected');

	$.get('{{route('tools.chord-finder.analyse')}}', {notes: input, root: root, tool: tool}, function(response) {
		$('#notes-container').html(response);
        $('html,body').scrollTop(0);
	}).fail(function(response) {
        showError(response.responseJSON.message);
	});
}

function showError(response) {
    $('#modal-error .modal-body div#error-report').text(response);
    $('#modal-error').modal('show');
}

$('#modal-error').on('hide.bs.modal', function() {
    reload();
});

function reload(full = false) {
    $('button#submit-notes').text($('button#submit-notes').attr('data-text')).prop('disabled', false);
    $('.input-overlay').hide();
    
    if (full) {
        input = [];
        reset('.dot');
        reset('.note');
        $('#options-buttons').html('').parent().removeClass('border');
        $('#options-container').hide();
        $('#root-buttons').html('');
        $('#root-container').hide();
    }
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

const ucfirst = (s) => {
  if (typeof s !== 'string') return ''
  return s.charAt(0).toUpperCase() + s.slice(1)
}
</script>
@endpush