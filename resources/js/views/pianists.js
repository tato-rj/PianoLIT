(function () {
    'use strict';
    const container = document.querySelector('[data-pianist-recordings]');
    if (!container) return;
    const artistId = container.getAttribute('data-itunes');
    const status = container.querySelector('[data-recordings-status]');
    const results = container.querySelector('[data-recordings-results]');
    const retry = container.querySelector('[data-recordings-retry]');
    const template = document.getElementById('pianist-album-template');
    let pending = false;

    function appleUrl(value, artwork) {
        if (typeof value !== 'string') return null;
        try {
            const url = new URL(value);
            const allowed = artwork ? /(^|\.)mzstatic\.com$/i.test(url.hostname) : /^(music|itunes)\.apple\.com$/i.test(url.hostname);
            return url.protocol === 'https:' && allowed && !url.username && !url.password && !url.port ? url.href : null;
        } catch (error) { return null; }
    }

    function failure() {
        status.textContent = 'We couldn’t load these recordings. Please try again.';
        retry.hidden = false;
    }

    function renderAlbum(album, href) {
        const card = template.content.firstElementChild.cloneNode(true);
        const link = card.querySelector('[data-album-link]');
        link.href = href;
        const title = typeof album.collectionName === 'string' ? album.collectionName.trim().replace(/[.\s]+$/, '') : '';
        card.querySelector('[data-album-title]').textContent = title || 'Untitled album';
        const artwork = appleUrl(album.artworkUrl100, true);
        if (artwork) card.querySelector('[data-album-image]').src = artwork;
        else card.querySelector('[data-album-art]').hidden = true;
        const details = [];
        if (typeof album.releaseDate === 'string' && /^\d{4}-\d{2}-\d{2}/.test(album.releaseDate)) details.push(album.releaseDate.slice(0, 4));
        if (Number.isInteger(album.trackCount) && album.trackCount > 0) details.push(album.trackCount + (album.trackCount === 1 ? ' track' : ' tracks'));
        card.querySelector('[data-album-details]').textContent = details.join(' · ');
        results.appendChild(card);
    }

    function load() {
        if (pending || !/^[1-9]\d*$/.test(artistId || '')) return;
        pending = true;
        retry.hidden = true;
        retry.disabled = true;
        container.setAttribute('aria-busy', 'true');
        status.textContent = 'Loading albums from Apple Music…';
        results.textContent = '';
        window.jQuery.ajax({
            url: 'https://itunes.apple.com/lookup',
            data: {id: artistId, entity: 'album', limit: 200},
            dataType: 'jsonp',
            timeout: 15000,
            success: function (response) {
                if (!response || !Array.isArray(response.results)) { failure(); return; }
                const seen = new Set();
                let count = 0;
                response.results.forEach(function (album) {
                    if (!album || album.collectionType !== 'Album') return;
                    const href = appleUrl(album.collectionViewUrl, false);
                    if (!href) return;
                    const key = typeof album.collectionId === 'string' || typeof album.collectionId === 'number' ? String(album.collectionId) : href;
                    if (seen.has(key)) return;
                    seen.add(key);
                    renderAlbum(album, href);
                    count += 1;
                });
                status.textContent = count ? 'Showing ' + count + (count === 1 ? ' album on Apple Music' : ' albums on Apple Music') : 'No albums are listed for this pianist yet.';
            },
            error: failure,
            complete: function () {
                pending = false;
                retry.disabled = false;
                container.setAttribute('aria-busy', 'false');
            }
        });
    }

    retry.addEventListener('click', load);
    load();
})();
