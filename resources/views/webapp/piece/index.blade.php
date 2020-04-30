@extends('webapp.layouts.app')

@push('header')
<link rel="stylesheet" href="https://cdn.plyr.io/3.6.1/plyr.css" />
<style type="text/css">
.mirror {
	-webkit-transform: scaleX(-1);
	transform: scaleX(-1);
}

.hands-lg {font-size: 240%;}

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
<script src="https://cdn.plyr.io/3.6.1/plyr.js"></script>
<script type="text/javascript">
$(document).on('click', '#select-hand button', function() {
	$('#select-hand button').disable();

	let $player = null;
	let $hand = $(this);

	$hand.toggleClass('text-muted opacity-4 text-teal');

	let rh = $hand.hasClass('text-teal');
	let lh = $hand.siblings('button').hasClass('text-teal');	
	
	stopAudio();
	resetSpeed();
	hidePlayers();

	if (rh == lh) {
		$player = $('#full-player');
	} else {
		$player = $($hand.data('target'));		
	}

	showPlayer($player);
	
	$player.get(0).load();
	
	if (rh || lh)
		$player.get(0).play();

	$player.get(0).oncanplay = function() {
	    $('#select-hand button').enable();
	};
});

$(document).on('change', 'input#audio-speed', function() {
	let speed = $(this).val();
	let label = speed != 1 ? ' - ' + speed + 'x normal speed' : null;

	$('.audio-control:visible').get(0).playbackRate = speed;
	$('#speed-label').text(label);
});

$(document).on('click', '#close-player', function() {
	stopAudio();
	$('#bottom-popup').fadeOut('fast');
});

$(document).on('click', '#player-header > .flex-grow, #toggle-player', function() {
	$('#player-body').toggle();
	$('#toggle-player i').toggleClass('fa-chevron-down fa-chevron-up');
});

$(document).on('click', '#expand-player', function() {
	$(this).find('i').toggleClass('fa-expand fa-compress');
	$('#player-body > div:first-of-type').toggleClass('flex-column align-items-center');
	$('#select-hand').toggleClass('mr-3 mb-3 hands-lg');
	$('#select-hand button').toggleClass('mx-2').find('>div:last-of-type').toggle();
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
	$('audio').each(function() {
		$(this).get(0).pause();
		$(this).get(0).currentTime = 0;
	});
}

function resetSpeed() {
	$('#audio-speed').val(1);
	$('#speed-label').text('');
	$('.audio-control').each(function() {
		$(this).get(0).playbackRate = 1;
	});
}

function hidePlayers() {
	$('.audio-control').addClass('d-none');
}

function showPlayer(player) {
	player.removeClass('d-none');	
} 
</script>

<script type="text/javascript">
$('button[data-action="video"]').on('click', function() {
	stopVideo();
	let videoId = $(this).data('target');
	let player = new Plyr(videoId);
	$(this).find('> div:first-of-type').hide();
	$(videoId).show();
	$(this).closest('.video-container').addClass('border rounded p-2');
});

function stopVideo(reset = true) {
	$('video').each(function() {
		let $video = $(this);
		$video.get(0).pause();

		if (reset) {
			$video.get(0).currentTime = 0;
			$video.hide();
			$video.closest('.video-container').removeClass('border rounded p-2 ').find('div').show();
		}
	});
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
  if (document[hidden]) {
  	stopAudio();
  	stopVideo(reset = false);
  }
}

if (typeof document.addEventListener === "undefined" || hidden === undefined) {
  console.log("This demo requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
} else {
  document.addEventListener(visibilityChange, handleVisibilityChange, false);
}
</script>
@endpush