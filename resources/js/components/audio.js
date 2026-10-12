var audio = new Audio;

$(document).on('click', '.play-clip', function() {
  let $icon = $(this).find('i');
  let src = $(this).attr('data-src');

  if (src) {
    $('.play-clip i').not($icon).removeClass('icon-circle-stop').addClass('icon-circle-play');
    stop();

    if ($icon.hasClass('icon-circle-play'))
      play(src);

    $icon.toggleClass('icon-circle-play icon-circle-stop');
  }
});

function stop() {
  if (typeof window !== 'undefined' && window.PianoActivity) window.PianoActivity.stopMedia(audio);
  audio.pause();
  audio.removeAttribute('src');
  audio.load();
}

function play(src) {
  if (typeof window !== 'undefined' && window.PianoActivity) window.PianoActivity.media(audio, {media_kind: 'audio', media_id: src.split(/[?#]/)[0].split('/').pop().slice(0, 300)});
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
  $('.play-clip i').removeClass('icon-circle-stop').addClass('icon-circle-play');
}

audio.addEventListener('ended', resetClipIcons);
audio.addEventListener('error', resetClipIcons);
