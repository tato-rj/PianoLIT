@extends('webapp.layouts.app', ['title' => $piece->short_name])

@push('header')
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.min.js"></script>
<style type="text/css">
video::-webkit-media-controls {
     visibility: hidden;
}

video::-webkit-media-controls-enclosure {
     visibility: visible;
}

.clap-shadow {
	position: absolute;
	left: 0;
	bottom: 2px;
	animation-duration: .5s !important;
}

.plyr--video {
	height: 100%;
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

.screen-lock-overlay {
  position: fixed;
  top: 0;
  left: 0;
  background: rgba(0,0,0,0.7);
  z-index: 10000;
  width: 100%;
  height: 100vh;
}

.nav-tabs .active {
    font-weight: inherit !important; 
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
		@include('webapp.piece.tabs.score')
		@include('webapp.piece.tabs.lessons')
		@include('webapp.piece.tabs.tutorial')
	</div>
</section>

@include('webapp.piece.components.panel')
@include('webapp.piece.performances.overlay')
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.0.3/resumable.min.js"></script>
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>

{{-- START OF PERFORMANCE SCRIPTS --}}

@if(local() || auth()->user()->id === 284)
<script type="text/javascript">
let $progressBar = $('.progress-bar');
let $uploadOverlay = $('#upload-overlay');
let $chooseButton = $('#choose-video');
let $confirmModal = $('#confirm-performance-modal');
let $confirmButton = $('#confirm-performance-button');
let $progress = $('.progress');
let $loadingText = $('#loading-text');
let resumable;

function startRequest()
{
	axios.get('{{ route('webapp.users.performances.upload-url', $piece) }}')
			 .then(function(response) {
			 	launchResumable(response.data);
			 })
			 .catch(function(error) {
			 	console.log(error);
			 });
}

startRequest();

function launchResumable(data)
{
	resumable = new Resumable({
	    target: data.url,
	    query:{
	        _token:'{{ csrf_token() }}',
	        secret: data.secret,
	        origin: 'webapp',
	        user_id: data.user.id,
	        piece_id: data.piece.id,
	        email: data.user.email,
	        notes: ''
	    },
	    fileType: ['mp4', 'MOV', 'mov'],
	    maxFileSize: 500000000,
		maxFiles: 1,
	    headers: {
	        'Accept' : 'application/json'
	    },
	    testChunks: false,
	    throttleProgressCallbacks: 1,
	});

	resumable.assignBrowse($chooseButton[0]);

	$confirmButton.on('click', function() {
	    if (resumable.files.length) {
	        $(this).prop('disabled', true);
	        resumable.upload();
	        $uploadOverlay.show();
	    }
	});

	resumable.on('fileAdded', function (file) {
	    showProgress();
	    showPreview(file);
	    showModal();
	});

	resumable.on('fileProgress', function (file) {
	    let percentage = Math.floor(file.progress() * 100);

	    updateProgress(percentage);
	    nextLoadingText(percentage);
	});

	resumable.on('fileSuccess', function (file, response) {
	    setTimeout(function() {
	        completeProgress();

	        setTimeout(function() {
	            $('#create-performance-form').submit();
	        }, 2000);
	    }, 1000);
	});

	resumable.on('fileError', function (file, response) {
		let error = JSON.parse(response);
		let message = Object.values(error)[0];

	    alert(message);
	    reset();
	});
}

$confirmModal.on('hidden.bs.modal', function() {
	resumable.cancel();
});

function showPreview(instance) {
	let $preview = $('#preview-performance source');

	  $preview[0].src = URL.createObjectURL(instance.file);
	  $preview.parent()[0].load();
}

function showModal() {
	$confirmModal.modal('show');
}

function showProgress() {
    $progress.find('.progress-bar').css('width', '0%');
    $progress.find('.progress-bar').html('0%');
    $progress.find('.progress-bar').removeClass('bg-success');
    $progress.show();
}

function updateProgress(value) {
    $progress.find('.progress-bar').css('width', `${value}%`);
    $progress.find('.progress-bar').html(`${value}%`);
}

function hideProgress() {
    $progress.hide();
}

let startTime;
let canChangeSentence = true;

function nextLoadingText(percentage) {
    if (! canChangeSentence)
        return null;

    let array = $loadingText.data('sentences');
    let index = Math.floor(percentage/Math.floor(100 / array.length));

    if (percentage > 90) {
        $loadingText.text(array.pop());

        canChangeSentence = false;
    } else {
        if (moment().diff(startTime, 'seconds') % 4 === 0) {
            $loadingText.text(array[index]);
        }
    }
}

function completeProgress() {
    $progressBar.removeClass('progress-bar-striped progress-bar-animated')
                .addClass('bg-green')
                .html('<i class="fa-solid fa-check fa-xl"></i>')
                .parent()
                .addClass('rubberBand');

    $loadingText.text('Done!');
}

function reset() {
    showProgress();
    hideProgress();
    $uploadOverlay.fadeOut('fast');
    $confirmModal.modal('hide');
    $confirmButton.prop('disabled', false);
}

$('#confirm-performance-modal').on('hide.bs.modal', function() {
    $('#preview-performance').get(0).pause();
    $('#preview-performance').get(0).currentTime = 0;
});
</script>
<script type="text/javascript">
function clap($hands) {
	$hands.removeClass('text-grey').addClass('text-orange');
	
	let $clone = $hands.clone();

	$clone.addClass('clap-shadow animated text-orange fadeOutUp').appendTo($hands.parent());

	setTimeout(function() {
	  $clone.remove();
	}, 500);
}

function isClapping() {
	return $('i.clap-shadow').length;
}

$('.clap').on('click', function() {
	let $hands = $(this).find('i');
	let $counter = $(this).siblings('.claps-count');

	if (! isClapping()) {
		$counter.removeClass('heartBeat');

		axios.post($(this).data('url'), {user_id: app.user.id})
				 .then(function(response) {
				 	$counter.text(response.data['claps_sum']);
				 	$counter.addClass('heartBeat');

				 	clap($hands);
				 });
	}
});
</script>
@endif

{{-- END OF PERFORMANCE SCRIPTS --}}

<script type="text/javascript">
// LIMIT COMPOSER BIO ON THE ABOUT SECTION TO 4 LINES
$('#composer-bio').clamp(4);

// UPDATE URL WHEN A NEW TAB IS SELECTED
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
	let plyrsArray = [];

	function initPlyrs(videoId)
	{
		let player = new Plyr(videoId, {
			ratio: '16:9',
		});

		plyrsArray.push(player);

		player.on('play', function() {
			stopOtherPlyrs(player);
		});
	}

	function stopOtherPlyrs(player)
	{
		plyrsArray.forEach(function(video) {
			if (video.playing && video != player)
				video.stop();
		});
	}

	$('.video-container').each(function() {
		let videoId = '#'+ $(this).find('video').attr('id');

		initPlyrs(videoId);
	});

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

		if (reset && $video.parents('.video-container').length) {
			$video.get(0).currentTime = 0;
			$video.closest('.video-container').removeClass('border rounded-sm p-2 ').find('div').show();
			$video.remove();
		}
	});
}
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
{{-- TRIGGER LINK ON CLICK, NOT WHILE DRAGGING --}}
<script type="text/javascript">
 $(function() {
    var isDragging = false;
    $('.search-card, .piece-card')
    .mousedown(function() {
        $(window).mousemove(function() {
            isDragging = true;
            $(window).unbind("mousemove");
        });
    })
    .mouseup(function() {
        var wasDragging = isDragging;
        isDragging = false;
        $(window).unbind("mousemove");
        if (!wasDragging) {
            search($(this));
        }
    });
  });

function search(element) {
	goTo(element.attr('data-url'));
}
</script>

@endpush
