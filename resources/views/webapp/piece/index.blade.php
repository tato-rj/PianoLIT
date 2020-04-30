@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.mirror {
	-webkit-transform: scaleX(-1);
	transform: scaleX(-1);
}

.btn-outline {border-width: 1.4px;}
.btn-default {padding: .6em 2.8em;}

.timeline-event:before {
	content: '';
	width: 12px;
	height: 12px;
	border-radius: 50%;
	background-color: #dee2e6;
	position: absolute;
	left: -7px;
	bottom: 50%;
	transform: translateY(50%);
}

.timeline-highlighted:before {
	background-color: #0055fe!important;
    -webkit-box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.header')

<section id="tabs-container">
	@include('webapp.piece.nav')
	<div class="tab-content p-3">
		@include('webapp.piece.tabs.audio')
		@include('webapp.piece.tabs.video')
		@include('webapp.piece.tabs.score')
		@include('webapp.piece.tabs.timeline')
	</div>

</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">
$(document).on('click', '#select-hand button', function() {
	let $hand = $(this);
	let player = document.getElementById('audio-control');
	
	$hand.toggleClass('text-muted opacity-4 text-teal');

	let rh = $hand.hasClass('text-teal');
	let lh = $hand.siblings('button').hasClass('text-teal');

	if (rh == lh) {
		player.src = $('#select-hand').data('audio');
	} else {
		player.src = $('#select-hand button.text-teal').data('audio');
	}

	if (rh || lh)
		player.play();
});

$(document).on('change', 'input#audio-speed', function() {
	let speed = $(this).val();
	let label = speed != 1 ? ' - ' + speed + 'x normal speed' : null;
	document.getElementById('audio-control').playbackRate = speed;
	$('#speed-label').text(label);
});

$(document).on('click', '#close-player', function() {
	stopAudio();
	$('#bottom-popup').fadeOut('fast');
});

$(document).on('click', '#player-header > div:first-of-type, #toggle-player', function() {
	$('#player-body').toggle();
	$(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
});
</script>
<script type="text/javascript">
$('button#launch-audio').click(function() {
	let $btn = $(this);
	$btn.disable();

	axios.get($btn.data('url'))
		.then(function(response) {
  			$('#bottom-popup-content').html(response.data)
  			$('#bottom-popup-content > div').width($('main').width());
  			$('#bottom-popup').show();
		})
  		.catch(function(error) {
  			$('#bottom-popup').fadeOut('fast');
  		})
  		.then(function() {
  			$btn.enable();
  		});
});

function stopAudio() {
	let players = document.getElementsByTagName('audio');
	for (i=0; i<players.length; i++) {
		players[i].pause();
		players[i].currentTime = 0;
	}
}
</script>

<script type="text/javascript">
var hidden, visibilityChange; 
if (typeof document.hidden !== "undefined") {
  hidden = "hidden";
  visibilityChange = "visibilitychange";
} else if (typeof document.msHidden !== "undefined") {
  hidden = "msHidden";
  visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
  hidden = "webkitHidden";
  visibilityChange = "webkitvisibilitychange";
}

function handleVisibilityChange() {
  if (document[hidden]) stopAudio();
}

if (typeof document.addEventListener === "undefined" || hidden === undefined) {
  console.log("This demo requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
} else {
  document.addEventListener(visibilityChange, handleVisibilityChange, false);
}
</script>
@endpush