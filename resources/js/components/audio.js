var audio = new Audio;

$(document).on('click', '.play-clip', function() {
  let $icon = $(this).find('i');
  let src = $(this).attr('data-src');

  if (src) {
    $('.play-clip i').not($icon).removeClass('fa-stop-circle').addClass('fa-play-circle');
    stop();

    if ($icon.hasClass('fa-play-circle'))
      play(src);

    $icon.toggleClass('fa-play-circle fa-stop-circle');
  }
});

function stop() {
  audio.pause();
  audio.removeAttribute('src');
  audio.load();
}

function play(src) {
  audio.src = src;
  var playback = audio.play();
  if (playback && playback.catch) {
    playback.catch(function() {
      if (audio.getAttribute('src') === src && audio.paused) {
        resetClipIcons();
      }
    });
  }
}

function resetClipIcons() {
  $('.play-clip i').removeClass('fa-stop-circle').addClass('fa-play-circle');
}

audio.addEventListener('ended', resetClipIcons);
audio.addEventListener('error', resetClipIcons);
