(function () {
  'use strict';

  var createModal = document.querySelector('#add-modal.autoshow');
  if (createModal) window.bootstrap.Modal.getOrCreateInstance(createModal).show();

  var form = document.querySelector('[data-pianist-editor]');
  if (form) initializeEditor(form);

  function initializeEditor(form) {
    var tabs = Array.from(form.querySelectorAll('[role="tab"]'));
    var section = form.querySelector('[data-pianist-section]');
    var saveStatus = form.querySelector('[data-save-status]');
    var saves = Array.from(document.querySelectorAll('[data-pianist-save]'));
    var restored = form.getAttribute('data-restored') === 'true';
    var submitting = false;
    var baseline = snapshot();

    function snapshot() {
      return JSON.stringify(Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea')).filter(function (control) {
        return control.name && !control.disabled;
      }).map(function (control) {
        return [control.name, control.type === 'file' ? Array.from(control.files).map(function (file) {
          return [file.name, file.size, file.lastModified];
        }) : control.value];
      }));
    }
    function updateStatus() {
      if (saveStatus) saveStatus.textContent = submitting ? 'Saving changes…' : (restored || snapshot() !== baseline ? 'Unsaved changes' : 'All changes saved');
    }
    function selectedSection() {
      var active = tabs.find(function (tab) { return tab.classList.contains('active'); });
      if (active) section.value = active.getAttribute('href').slice(1);
    }
    function showSection(id) {
      var tab = tabs.find(function (item) { return item.getAttribute('href') === '#' + id; });
      if (tab) window.bootstrap.Tab.getOrCreateInstance(tab).show();
      selectedSection();
    }
    form.addEventListener('shown.bs.tab', selectedSection);
    form.addEventListener('input', updateStatus);
    form.addEventListener('change', updateStatus);
    form.addEventListener('invalid', function (event) {
      var first = form.querySelector('input:invalid, select:invalid, textarea:invalid');
      if (event.target !== first) return;
      var panel = first.closest('.tab-pane');
      if (panel) showSection(panel.id);
    }, true);
    form.addEventListener('submit', function (event) {
      if (submitting) { event.preventDefault(); return; }
      selectedSection();
      submitting = true;
      saves.forEach(function (button) { button.disabled = true; });
      updateStatus();
    });
    window.addEventListener('pageshow', function () {
      submitting = false;
      saves.forEach(function (button) { button.disabled = false; });
      updateStatus();
    });

    var portrait = form.querySelector('[data-pianist-portrait]');
    var cover = form.querySelector('[name="cover"]');
    var portraitStatus = form.querySelector('[data-portrait-status]');
    if (portrait && cover && portraitStatus) {
      var originalSource = portrait.getAttribute('src');
      var previewUrl;
      cover.addEventListener('change', function () {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        portrait.src = originalSource;
        var file = cover.files[0];
        portraitStatus.textContent = 'JPG, PNG, GIF or WebP · up to 10 MB.';
        if (file && /^(image\/jpeg|image\/png|image\/gif|image\/webp)$/.test(file.type) && typeof URL.createObjectURL === 'function') {
          previewUrl = URL.createObjectURL(file);
          portrait.src = previewUrl;
          portraitStatus.textContent = file.name + ' selected. Save changes to update the portrait.';
        }
      });
    }
    var errorSection = form.getAttribute('data-error-section');
    if (errorSection) showSection(errorSection);
    else selectedSection();
    updateStatus();
  }

  var container = document.querySelector('[data-pianist-albums]');
  if (!container) return;
  var artistId = container.getAttribute('data-artist-id');
  var status = container.querySelector('[data-albums-status]');
  var results = container.querySelector('[data-albums-results]');
  var retry = document.querySelector('[data-pianist-albums-retry]');
  var pending = false;
  var loaded = false;
  var albumPanel = container.closest('.tab-pane');

  function appleUrl(value, artwork) {
    try {
      var url = new URL(value);
      var allowed = artwork ? /(^|\.)mzstatic\.com$/i.test(url.hostname) : /^(music|itunes)\.apple\.com$/i.test(url.hostname);
      return url.protocol === 'https:' && allowed ? url.href : null;
    } catch (error) { return null; }
  }

  function renderAlbum(album) {
    var column = document.createElement('div');
    column.className = 'col-lg-4 col-sm-6';
    var row = document.createElement('div');
    row.className = 'd-flex align-items-start gap-3';
    var artwork = appleUrl(album.artworkUrl100, true);
    if (artwork) {
      var image = document.createElement('img');
      image.src = artwork;
      image.alt = '';
      image.width = 64;
      image.height = 64;
      image.loading = 'lazy';
      image.className = 'rounded-sm flex-shrink-0';
      row.appendChild(image);
    }
    var details = document.createElement('div');
    var href = appleUrl(album.collectionViewUrl, false);
    var title = document.createElement(href ? 'a' : 'span');
    title.textContent = album.collectionName || 'Untitled album';
    if (href) {
      title.href = href;
      title.target = '_blank';
      title.rel = 'noopener noreferrer';
    }
    details.appendChild(title);
    if (typeof album.collectionPrice === 'number' && album.currency) {
      var price = document.createElement('p');
      price.className = 'small text-muted mb-0 mt-1';
      price.textContent = album.collectionPrice + ' ' + album.currency;
      details.appendChild(price);
    }
    row.appendChild(details);
    column.appendChild(row);
    results.appendChild(column);
  }

  function load() {
    if (pending || !artistId) return;
    pending = true;
    if (retry) retry.disabled = true;
    container.setAttribute('aria-busy', 'true');
    results.textContent = '';
    status.textContent = 'Loading albums…';
    $.ajax({
      url: 'https://itunes.apple.com/lookup',
      data: {id: artistId, entity: 'album'},
      dataType: 'jsonp',
      timeout: 15000,
      success: function (response) {
        loaded = true;
        var albums = response && Array.isArray(response.results) ? response.results.filter(function (item) {
          return item && item.collectionType === 'Album';
        }) : [];
        status.textContent = albums.length ? albums.length + (albums.length === 1 ? ' album on Apple Music.' : ' albums on Apple Music.') : 'No albums found for this artist ID.';
        albums.forEach(renderAlbum);
      },
      error: function () { status.textContent = 'Albums could not be loaded. Try reloading them.'; },
      complete: function () {
        pending = false;
        if (retry) retry.disabled = false;
        container.setAttribute('aria-busy', 'false');
      }
    });
  }

  if (retry) retry.addEventListener('click', load);
  if (albumPanel) {
    document.addEventListener('shown.bs.tab', function (event) {
      if (event.target.getAttribute('href') === '#' + albumPanel.id && !loaded) load();
    });
  }
  if (!albumPanel || albumPanel.classList.contains('active')) load();
})();
