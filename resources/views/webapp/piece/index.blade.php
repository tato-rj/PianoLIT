@extends('webapp.layouts.app', ['title' => $piece->medium_name_with_composer])

@push('header')
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
<script src="{{ mix('js/vendor/pdfjs/pdf.js') }}"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.worker.min.js';</script>
<style type="text/css">

video::-webkit-media-controls {
     visibility: hidden;
}

video::-webkit-media-controls-enclosure {
     visibility: visible;
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

.nav-tabs .active {
    font-weight: bold !important;
}

</style>
@endpush

@section('content')
@include('webapp.piece.header')

<section id="tabs-container">
	@include('webapp.piece.nav')
	<div class="tab-content">
		@include('webapp.piece.tabs.about')
		@include('webapp.piece.tabs.score')
		@include('webapp.piece.tabs.timeline')
		@include('webapp.piece.tabs.synthesia')
	</div>
</section>

@include('webapp.piece.components.panel')
@include('webapp.piece.share')
@if($piece->hasAudio())
@include('webapp.piece.components.audio-band')
@endif

@endsection

@push('scripts')
<script src="{{ mix('js/components/link-share.js') }}"></script>
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
<script src="{{ mix('js/views/piece-access.js') }}"></script>
<script src="{{ mix('js/views/video-moments.js') }}"></script>
@if($piece->hasAudio())
<script src="{{ mix('js/views/playlist-moments.js') }}"></script>
<script src="{{ mix('js/views/playlist-player.js') }}"></script>
@endif
<script src="{{ mix('js/views/piece-description.js') }}"></script>
@if($piece->score_path && $piece->isPublicDomain)
<script src="{{ mix('js/components/score-metronome.js') }}"></script>
@endif
@if(($hasMediaAccess || $hasScoreExportAccess) && $piece->score_path && $piece->isPublicDomain)
<script src="{{ mix('js/views/score-editor.js') }}"></script>
<script>
const printablePreview = document.getElementById('score-preview');
if (printablePreview && printablePreview.getAttribute('data-score-export-access') === '1') {
    printablePreview.querySelector('[data-print]').addEventListener('click', function () { ScoreEditor.printOriginal(printablePreview, pdfjsLib); });
}
const scoreEditorElement = document.getElementById('score-editor');
if (scoreEditorElement) {
    let scoreEditor = null;
    function showScoreEditor() {
        if (!scoreEditorElement.offsetWidth) return;
        if (!scoreEditor) {
            scoreEditor = new ScoreEditor.Editor(scoreEditorElement, pdfjsLib, axios);
        } else if (scoreEditor.pdf) {
            scoreEditor.render(scoreEditor.page).catch(function () { scoreEditor.renderError(); });
        }
    }
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', showScoreEditor);
    showScoreEditor();
}
</script>
@endif
@if(! $hasMediaAccess || ! $hasPerformanceAccess || ! $hasSynthesiaAccess || ! $hasAudioAccess)
<script>
PieceAccess.installPreviewGuards(document, function (kind, media) {
    document.querySelectorAll('audio, video').forEach(function (media) { media.pause(); });
    @guest('web')
    PianoLITAccessPrompts.show(kind === 'audio' ? '#signup-audio-modal' : '#signup-video-modal', kind);
    @else
    PianoLITAccessPrompts.show(media.getAttribute('data-access-prompt') || '{{ app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user()) }}', kind);
    @endguest
});
const scorePreview = document.getElementById('score-preview');
if (scorePreview) PieceAccess.renderScorePreview(scorePreview, pdfjsLib);
</script>
@endif



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
function stopAudio() {
    if (window.PieceAudioPlayer) window.PieceAudioPlayer.pause();
}
</script>

<script type="text/javascript">
	let plyrsArray = [];

	function initPlyrs(videoId, options = {})
	{
		let media = typeof videoId === 'string' ? document.querySelector(videoId) : videoId;
        if (!media) return;
        if (window.PianoActivity) window.PianoActivity.media(media, {media_kind: 'video', media_id: media.id || (media.closest('.tab-pane') ? media.closest('.tab-pane').id : '') || 'piece-video', piece_id: {{ $piece->id }}});
        let player = new Plyr(media, Object.assign({}, options, VideoMoments.markerOptions(media)));
        media.piecePlayer = player;
        const cleanupMoments = VideoMoments.attach(player, media);
        media.disposePiecePlayer = function () {
            if (cleanupMoments) cleanupMoments();
            plyrsArray = plyrsArray.filter(video => video !== player);
            player.destroy();
        };

		plyrsArray.push(player);
        player.elements.original.addEventListener('destroyed', function () { plyrsArray = plyrsArray.filter(video => video !== player); }, {once: true});

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

	$('.video-container video').each(function() {
        initPlyrs(this, {ratio: '16:9'});
    });

let videoRequest = 0;

$('button[data-action="video"]').on('click', function() {
	let $btn = $(this);
	let $icon = $btn.find('> div:first-of-type');

	if ($icon.is(':visible')) {
		stopVideo();
        const requestId = videoRequest;
		let $container = $btn.closest('.video-container');

		$btn.addClass('opacity-4').disable();

		axios.get($btn.data('url'))
			 .then(function(response) {
                $btn.removeClass('opacity-4').enable();
                if (requestId !== videoRequest) return;
                let $loaded = $('<div data-loaded-piece-video></div>').html(response.data);
                let media = $loaded.find('video').get(0);
                if (!media) return;
                $icon.hide();
                $container.append($loaded);
                $container.addClass('border rounded-1 p-2');
                try {
                    initPlyrs(media);
                } catch(e) {
                    $(media).attr('controls', true);
                }
			 })
			 .catch(function(error) {
                $btn.removeClass('opacity-4').enable();
                if (requestId === videoRequest) alert('The video could not be loaded. Please try again.');
			 });
	}
});

function stopVideo(reset = true) {
    videoRequest++;
    $('video').each(function() { this.pause(); });
    if (reset) {
        $('[data-loaded-piece-video]').each(function () {
            const $loaded = $(this);
            const media = $loaded.find('video').get(0);
            if (media && media.disposePiecePlayer) media.disposePiecePlayer();
            const $container = $loaded.closest('.video-container');
            $container.removeClass('border rounded-1 p-2');
            $container.find('button[data-action="video"] > div:first-of-type').show();
            $loaded.remove();
        });
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
  if (document[hidden]) {
	stopAudio();
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
