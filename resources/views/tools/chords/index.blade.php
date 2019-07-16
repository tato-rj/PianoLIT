@extends('layouts.app')

@push('header')
<style type="text/css">
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
    color:#212529;
    background-color:#e2e6ea;
    border-color:#dae0e5;
}

</style>
@endpush

@section('content')
<div class="mb-5 text-center">
	<h3>Chord Finder</h3>
	<p class="text-grey">Just tell us the notes and we'll show you all the possible chords you can make with them</p>
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
        $('.chords-results button').removeClass('btn-chord-selected');
        $(this).addClass('btn-chord-selected');
        let notes = JSON.parse($(this).attr('data-notes'));
        let noteIndex = 0;
        let chord = [];

        notes.forEach(function(element, index) {
            let note = element.replace('+', '#').replace('-', 'b').replace('2', '');
            let $key = findKey(note, noteIndex);
            chord.push(note + $key.attr('data-octave'));
            noteIndex = $key.hasClass('keyboard-black-key') ? $key.parent().next().index() : $key.index();

            setTimeout(function() {
                press($key, 150, false);
                highlight($key);
            }, 200 * index);
        });

        setTimeout(function() {
            piano.triggerAttackRelease(chord, "1n");
        }, (notes.length + 1) * 200);
    }
});

</script>
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
        setTimeout(function() {
            submit();
        }, 1500);
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