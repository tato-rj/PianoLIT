(function ($) {
  const $circle = $('#circle');
  const $keys = $('#letters > g');
  const $tabs = $('#mode-tabs');
  const $playButton = $('#circle-play-scale');
  const order = [];
  let rotation = 0;
  let selectedIndex = 0;
  let playbackTimers = [];
  let playing = false;

  function data($key, name) {
    return JSON.parse($key.attr(name));
  }

  function currentKey() {
    return $keys.filter('.key').first();
  }

  function currentMode() {
    return $tabs.find('.active').attr('data-name') || 'major';
  }

  function pill(target, templateId, values) {
    const template = document.getElementById(templateId);
    if (!template) return;
    const $pill = $(template.content.firstElementChild.cloneNode(true));
    Object.keys(values).forEach(function (selector) {
      $pill.find(selector).text(String(values[selector]).replace(/&#176;/g, '°'));
    });
    $(target).append($pill);
  }

  function render() {
    const $key = currentKey();
    ['major', 'minor', 'enharmonic'].forEach(function (mode) {
      const $panel = $('#mode-' + mode);
      const name = $key.attr(mode === 'enharmonic' ? 'key-enharmonic-major' : 'key-' + mode);
      const relative = $key.attr(mode === 'enharmonic' ? 'key-enharmonic-minor' : (mode === 'minor' ? 'key-major' : 'key-minor'));
      const signature = mode === 'enharmonic' ? $key.attr('enharmonic-id') : $key.attr('id');
      const $image = $panel.find('.key-signature');
      const theoretical = mode === 'enharmonic' && signature === 'key-loading';
      $panel.find('.key-name').text(name);
      $panel.find('.key-relative').text(relative);
      $image.attr('src', $image.attr('data-folder') + '/' + signature + '.svg')
        .attr('alt', 'Key signature for ' + name).toggleClass('d-none', theoretical);
      $panel.find('.key-signature-note').toggleClass('d-none', !theoretical);

      const $neighbors = $panel.find('.key-neighbors, .key-enharmonic-neighbors').empty();
      data($key, mode === 'enharmonic' ? 'key-enharmonic-neighbors' : 'key-neighbors').forEach(function (neighbor) {
        pill($neighbors, 'circle-key-pill', {'.key-label': neighbor});
      });
      const $roman = $panel.find('.key-roman').empty();
      const numerals = data($key, 'key-' + mode + '-roman');
      Object.keys(numerals).forEach(function (numeral) {
        pill($roman, 'circle-chord-pill', {'.roman-numeral': numeral, '.chord-name': numerals[numeral]});
      });
      ['tonic', 'dominant', 'subdominant'].forEach(function (group) {
        const $group = $panel.find('.key-' + group).empty();
        data($key, 'key-' + mode + '-' + group).forEach(function (chord) {
          $group.append($('<div>').text(chord));
        });
      });
    });
    const label = $key.attr(currentMode() === 'enharmonic' ? 'key-enharmonic-major' : 'key-' + currentMode()).split(' ');
    $('[data-circle-tonic]').text(label[0].replace(/#/g, '♯').replace(/b/g, '♭'));
    $('[data-circle-mode]').text(label[1]);
    $('#mode-panels').css('opacity', 1);
    highlightKeyboard();
  }

  function scaleKeys(includeOctave) {
    const scale = data(currentKey(), 'key-' + currentMode() + '-scale');
    const keys = $('#circle-piano .keyboard-key').toArray();
    // Some theoretical spellings (such as C double flat) are absent from the
    // shared keyboard's aliases. Resolve their pitch here without changing it.
    const pitch = function (note) {
      const match = /^([a-g])([#b]*)$/.exec(note);
      if (!match) return null;
      const naturals = {c: 0, d: 2, e: 4, f: 5, g: 7, a: 9, b: 11};
      const offset = Array.from(match[2]).reduce(function (value, accidental) { return value + (accidental === '#' ? 1 : -1); }, 0);
      return (naturals[match[1]] + offset + 12) % 12;
    };
    const matches = function (key, note) {
      return JSON.parse(key.getAttribute('data-names')).includes(note) || pitch(key.getAttribute('data-name')) === pitch(note);
    };
    const start = keys.findIndex(function (key) { return matches(key, scale[0]); });
    if (start === -1) return [];
    const selected = keys.slice(start, start + 12).filter(function (key) {
      return scale.some(function (note) { return matches(key, note); });
    });
    if (includeOctave && keys[start + 12]) selected.push(keys[start + 12]);
    return selected;
  }

  function highlightKeyboard() {
    $('#circle-piano .keyboard-key > .dot').hide();
    scaleKeys(false).forEach(function (key) { $(key).children('.dot').show(); });
  }

  function playbackState(active) {
    playing = active;
    $playButton.attr('aria-pressed', active ? 'true' : 'false');
    $playButton.find('[data-play-label]').text(active ? 'Stop scale' : 'Play scale');
    $playButton.find('[data-play-icon]').toggleClass('d-none', active);
    $playButton.find('[data-stop-icon]').toggleClass('d-none', !active);
  }

  function stopScale() {
    playbackTimers.forEach(clearTimeout);
    playbackTimers = [];
    if (playing && typeof release === 'function') release();
    playbackState(false);
  }

  function playScale() {
    if (playing) { stopScale(); return; }
    const notes = scaleKeys(true);
    if (!notes.length || typeof press !== 'function') return;
    const $status = $('#circle-audio-status').empty().addClass('d-none');
    if (typeof piano !== 'undefined' && !piano.loaded) {
      $status.text('The piano is still loading. Please try again in a moment.').removeClass('d-none');
      return;
    }
    // Resume the existing sampler's context directly from this user gesture.
    if (window.Tone && Tone.context && typeof Tone.context.resume === 'function') {
      Tone.context.resume().catch(function () {
        stopScale();
        $status.text('The piano could not start. Please tap Play scale again.').removeClass('d-none');
      });
    }
    playbackState(true);
    notes.forEach(function (key, index) {
      playbackTimers.push(setTimeout(function () { press($(key), 300, false); }, index * 420));
    });
    playbackTimers.push(setTimeout(stopScale, notes.length * 420));
  }

  function selectKey(id, focus) {
    const index = order.indexOf(id);
    if (index === -1) return;
    stopScale();
    let steps = index - selectedIndex;
    if (steps > 6) steps -= 12;
    if (steps < -6) steps += 12;
    rotation -= steps * 30;
    selectedIndex = index;
    $circle.css('transform', 'rotate(' + rotation + 'deg)');
    $circle.find('.circle-key-label').css('transform', 'rotate(' + (-rotation) + 'deg)');
    $keys.removeClass('key').attr({'aria-pressed': 'false', tabindex: '-1'});
    const $selected = $keys.filter(function () { return this.id === id; }).addClass('key').attr({'aria-pressed': 'true', tabindex: '0'});
    render();
    if (focus && $selected[0]) $selected[0].focus({preventScroll: true});
  }

  function move(direction, focus) {
    selectKey(currentKey().attr('control-' + direction).slice(1), focus);
  }

  function addHitArea(key, index) {
    // Broad sectors make both the major and relative-minor labels easy to tap.
    const point = function (radius, angle) {
      const radians = angle * Math.PI / 180;
      return (300 + radius * Math.cos(radians)).toFixed(3) + ',' + (300 + radius * Math.sin(radians)).toFixed(3);
    };
    const start = index * 30 - 105, end = start + 30;
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', 'M' + point(280, start) + 'A280,280 0 0 1 ' + point(280, end) + 'L' + point(130, end) + 'A130,130 0 0 0 ' + point(130, start) + 'Z');
    path.setAttribute('fill', 'transparent');
    path.setAttribute('class', 'circle-hit-area');
    path.setAttribute('pointer-events', 'all');
    key.insertBefore(path, key.firstChild);
  }

  $(function () {
    if (!$circle.length) return;
    let id = 'key-c';
    for (let i = 0; i < 12; i++) {
      order.push(id);
      id = document.getElementById(id).getAttribute('control-left').slice(1);
    }
    $keys.each(function () { addHitArea(this, order.indexOf(this.id)); });
    selectedIndex = order.indexOf(currentKey().attr('id'));
    $keys.on('click', function () { selectKey(this.id, false); });
    $keys.on('keydown', function (event) {
      if (event.altKey || event.ctrlKey || event.metaKey) return;
      if (['ArrowLeft', 'ArrowRight', 'Home', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        if (event.key === 'ArrowLeft') move('right', true);
        else if (event.key === 'ArrowRight') move('left', true);
        else selectKey(event.key === 'Home' ? 'key-c' : this.id, true);
      }
    });
    $('#wheel-controls button[direction]').on('click', function () { move($(this).attr('direction'), false); });
    $('#circle-reset').on('click', function () { $('#major-tab').tab('show'); selectKey('key-c', false); });
    $tabs.find('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () { stopScale(); render(); });
    $playButton.on('click', playScale);
    $('#circle-piano').on('pointerdown', '.keyboard-key', function () { if (playing) stopScale(); });
    $(window).on('pagehide', stopScale);
    $(document).on('show.bs.modal', stopScale);
    $(document).on('visibilitychange', function () { if (document.hidden) stopScale(); });
    render();
  });
})(jQuery);
