@extends('webapp.layouts.app')

@push('header')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.min.js"></script>
<style type="text/css">
#pdf-container .pdf-control:hover button {
	opacity: .6 !important;
}

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

#main-nav .nav-link, #main-nav .nav-link:hover {
	border: 0;
}

#main-nav .nav-link:not(.active) {
	color: #6c757d!important;
}

#main-nav .nav-link.active {
	background-color: inherit;
}

#main-nav .nav-item {
	position: relative;
}

#nav-border {
	position: absolute;
	bottom: 0;
	left: 0;
	width: 100%;
	height: 1px;
	background-color: #343a40;
}

.nav-outline {
	width: 100%;
	height: 1px;
	background-color: transparent;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.header')

<section id="tabs-container">
	@include('webapp.piece.nav')
	<div class="tab-content p-3">
		@include('webapp.piece.tabs.about')
		@include('webapp.piece.tabs.media')
		@include('webapp.piece.tabs.score')
		@include('webapp.piece.tabs.timeline')
	</div>

</section>

@include('webapp.piece.components.panel')
@include('webapp.piece.share')
@endsection

@push('scripts')
<script type="text/javascript">
$('#composer-bio').clamp(4);
</script>

<script type="text/javascript">
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href="#tab-' + url.split('#')[1] + '"]').tab('show');
    }
</script>

<script type="text/javascript">
$('#pdf-share').click(function() {
	let url = $(this).data('url');

	if (navigator.share) {
	    navigator.share({
	      title: "{{$piece->medium_name}}",
	      url: url
	    }).then(() => {
	      console.log('Thanks for sharing!');
	    })
	    .catch(console.log('Thanks for sharing!'));
	} else {
		alert('Sorry, sharing is not supported by this browser');
	}	
});
</script>
<script type="text/javascript">
$(document).ready(function() {
	const pdfurl = "{{storage($piece->score_path)}}";

	if (safari()) {
		$('.ios-only').show();
		$('.non-ios').hide();
	} else {
		$('.ios-only').hide();
		$('.non-ios').show();

		let pdfDoc = null, pageNum = 1, numPages = 0, padeIsRendering = false, pageNumIsPending = null;

		const scale = 1.5, canvas = document.querySelector('#score-pdf'), ctx = canvas.getContext('2d'), $loading = $('#pdf-loading');

		function renderPage(num) {
			pageIsRendering = true;
			$loading.show();
			pdfDoc.getPage(num).then(page => {
				const viewport = page.getViewport({scale: scale});
				canvas.height = viewport.height;
				canvas.width = viewport.width;

				page.render({
					canvasContext: ctx,
					viewport: viewport
				}).promise.then(() => {
					pageIsRendering = false;
					$loading.hide();
					if (pageNumIsPending !== null) {
						renderPage(pageNumIsPending);
						pageNumIsPending = null;
					}
				});
			});
		}

		function queueRenderPage(num) {
			if (pageIsRendering) {
				pageNumIsPending = num;
			} else {
				renderPage(num);
			}
		}

		function showPrevPage() {
			$('.pdf-control[right]').show();

			if (pageNum <= 2)
				$('.pdf-control[left]').hide();

			if (pageNum <= 1)
				return;

			pageNum--;
			queueRenderPage(pageNum);
		}

		function showNextPage() {
			$('.pdf-control[left]').show();

			if (pageNum >= numPages - 1)
				$('.pdf-control[right]').hide();

			if (pageNum >= numPages)
				return;

			pageNum++;
			queueRenderPage(pageNum);
		}

		function isLastPage() {
			return pageNum <= 1;
		}

		function isFirstPage() {
			return pageNum >= pdfDoc.numPages;
		}

		$('.pdf-control[left]').click(function() {showPrevPage()});

		$('.pdf-control[right]').click(function() {showNextPage()});

		pdfjsLib.getDocument({url: pdfurl}).promise.then(pdfDoc_ => {
			pdfDoc = pdfDoc_;
			numPages = pdfDoc.numPages;

			if (numPages < 2)
				$('.pdf-control').hide();

			renderPage(pageNum);
		}).catch(error => {
			console.log(error);
		});
	}
});
</script>

<script type="text/javascript">
function toggleHand($hand) {
	let play = false;
	let $selected = null;
	$hand.toggleClass('text-muted opacity-4 text-teal');
	$selected = $('#select-hand').find('.text-teal');
	play = $selected.length > 0;

	if ($selected.length != 1) {
		return {play: play, player: $('#full-player')};
	} else {
		return {play: play, player: $($selected.data('target'))};
	}
}

function play(player) {
	player.get(0).load();
	
	player.get(0).play();

	player.get(0).oncanplay = function() {
	    $('#select-hand button').enable();
	};
}

$(document).on('click', '#select-hand button', function() {
	$('#select-hand button').disable();

	let $hand = $(this);
	let selection = toggleHand($hand);
	
	stopAudio();
	resetSpeed();
	hidePlayers();
	
	showPlayer(selection.player);

	if (selection.play) {		
		play(selection.player);
	} else {
		$('#select-hand button').enable();
	}
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
	let $btn = $(this);
	let $icon = $btn.find('> div:first-of-type');

	if ($icon.is(':visible')) {
		stopVideo();
		let $container = $btn.closest('.video-container');

		$btn.addClass('opacity-4').disable();

		axios.get($btn.data('url'))
			 .then(function(response) {
			 	let html = response.data;
			 	let videoId = '#'+$(html).attr('id');

			 	$btn.removeClass('opacity-4').enable();
				$icon.hide();
			 	$container.append(html);
				$container.addClass('border rounded-sm p-2');
				try {
					new Plyr(videoId);
				} catch(e) {
					$(videoId).attr('controls', true);
				}
			 })
			 .catch(function(error) {
			 	console.log(error);
			 });
	}
});

function stopVideo(reset = true) {
	$('video').each(function() {
		let $video = $(this);
		$video.get(0).pause();

		if (reset) {
			$video.get(0).currentTime = 0;
			$video.closest('.video-container').removeClass('border rounded-sm p-2 ').find('div').show();
			$video.remove();
		}
	});
}
</script>

<script type="text/javascript">
$('#share-modal').on('show.bs.modal', function (e) {
  $('[data-toggle="fixed-panel"]').click();
})
</script>

<script type="text/javascript">
$('form#tutorial-request-form button[type="submit"]').on('click', function(e) {
	e.preventDefault();

	let $form = $('form#tutorial-request-form');

	checked = $form.find("input[type=checkbox]:checked").length;

	if(!checked) {
		$('#tutorial-alert').show();
	} else {
		$('#tutorial-alert').hide();
		$(this).disable();
		$form.submit();
	}
});
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
  console.log("This requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
} else {
  document.addEventListener(visibilityChange, handleVisibilityChange, false);
}

function iOS() {

  var iDevices = [
    'iPad Simulator',
    'iPhone Simulator',
    'iPod Simulator',
    'iPad',
    // 'MacIntel',
    'iPhone',
    'iPod'
  ];

  if (navigator.platform) {
    while (iDevices.length) {
      if (navigator.platform === iDevices.pop()){ return true; }
    }
  }

  return false;
}

function safari()
{
	var ua = navigator.userAgent.toLowerCase(); 
	if (ua.indexOf('safari') != -1) { 
		if (ua.indexOf('chrome') > -1) {
			return false;
		} else {
			return true;
		}
	}

	return false;
}
</script>

<script type="text/javascript">
jQuery.fn.match = function(element) {
	let pos = element.parent().position().left;
	let width = element.outerWidth();

	return this.css({
		width: width,
		left: pos+'px'
	}).empty();
};

let $border = $('#nav-border');

$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
  let $tab = $(e.target);
  $border.match($tab);
  $border.show();
});
</script>
@endpush
