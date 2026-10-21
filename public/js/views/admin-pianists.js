(function () {
  'use strict';

  var createModal = document.querySelector('#add-modal.autoshow');
  if (createModal) window.bootstrap.Modal.getOrCreateInstance(createModal).show();

  var container = document.querySelector('[data-pianist-albums]');
  if (!container) return;
  var artistId = container.getAttribute('data-artist-id');
  var status = container.querySelector('[data-albums-status]');
  var results = container.querySelector('[data-albums-results]');
  var retry = document.querySelector('[data-pianist-albums-retry]');
  var pending = false;

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
  load();
})();
