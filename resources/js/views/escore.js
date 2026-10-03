(function () {
    'use strict';

    function textColor(color) {
        var rgb = [1, 3, 5].map(function (offset) {
            var value = parseInt(color.substr(offset, 2), 16) / 255;
            return value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2] > 0.179 ? '#000000' : '#ffffff';
    }

    function fitText(text, size, width, height, measure) {
        var initialSize = size;
        function wrap() {
            var lines = [], line = '', used = 0;
            (text.match(/\r\n|\r|\n|[^\S\r\n]+|[^\s]+/gu) || []).forEach(function (token) {
                if (/^[\r\n]+$/.test(token)) { lines.push(line); line = ''; used = 0; return; }
                var tokenWidth = measure(token, size);
                if (used + tokenWidth > width && line) { lines.push(line); line = ''; used = 0; }
                if (!line && !token.trim()) return;
                (tokenWidth > width ? Array.from(token) : [token]).forEach(function (part) {
                    var partWidth = measure(part, size);
                    if (used + partWidth > width && line) { lines.push(line); line = ''; used = 0; }
                    line += part; used += partWidth;
                });
            });
            if (line) lines.push(line);
            return lines;
        }
        var lines;
        do {
            lines = wrap();
            if (lines.length * size * 1.2 <= height) break;
            size--;
        } while (size > 8);
        if (lines.length * size * 1.2 > height) {
            var normalized = text.replace(/\s+/gu, ' ');
            if (normalized !== text) return fitText(normalized, initialSize, width, height, measure);
        }
        return {text: lines.join('\n'), size: size};
    }

    function selectedIds(form) {
        return Array.from(form.querySelectorAll('[data-escore-piece]')).filter(function (row) {
            return !row.hidden && row.querySelector('[data-escore-select]').checked && row.dataset.eligible === 'true';
        }).map(function (row) { return row.dataset.escorePiece; });
    }

    function initWizard(form) {
        var modal = form.closest('.modal'), step = 1, pageNumber = 1, doc = null, metadata = null;
        var revision = 0, controller = null, timer = null, visible = false, downloading = false;
        var summaryObserver = null, endDrag = null;
        var imageCache = {}, thumbnailLimit = 24, renderRevision = 0;
        var q = function (selector) { return form.querySelector(selector); };
        var qa = function (selector) { return Array.from(form.querySelectorAll(selector)); };
        var status = q('[data-escore-status]'), next = q('[data-escore-next]');
        var cover = q('[data-escore-cover]'), mainCanvas = q('[data-escore-main-canvas]');
        var toggles = ['page_numbers', 'composer_names', 'include_edition', 'blank_pages'];

        function message(text, failed) {
            status.textContent = text;
            status.classList.toggle('text-danger', !!failed);
        }
        function valid() {
            return form.elements.title.value.trim().length > 0 && selectedIds(form).length > 0;
        }
        function controls() {
            next.disabled = downloading || !valid() || (step === 3 && !metadata);
            qa('[data-escore-step]').forEach(function (button) { button.disabled = downloading; });
            q('[data-escore-back]').disabled = downloading;
            qa('[data-escore-panel] input:not([data-escore-select]), [data-escore-panel] textarea, [data-escore-panel] select, [data-escore-color]').forEach(function (input) { input.disabled = downloading; });
            qa('[data-escore-select]').forEach(function (input) { input.disabled = downloading || input.closest('[data-escore-piece]').dataset.eligible !== 'true'; });
            qa('[data-escore-drag]').forEach(function (input) { input.disabled = downloading || input.closest('[data-escore-piece]').dataset.eligible !== 'true'; });
        }
        function refreshCover() {
            var color = form.elements.color.value, context = document.createElement('canvas').getContext('2d');
            cover.style.backgroundColor = color;
            cover.style.color = textColor(color);
            var subtitleLayout, imageCover = cover.dataset.escoreImageCover === 'true';
            ['title', 'subtitle', 'comment', 'bottom_text'].forEach(function (name) {
                var sizes = {title: [54, 460, 140], subtitle: [26, 408, 74], comment: [24, 408, 205], bottom_text: [18, 250, 38]};
                if (imageCover) sizes = {title: [60, 460, 110], subtitle: [34, 460, 90], comment: [26, 460, 110], bottom_text: [20, 125, 60]};
                var spec = sizes[name], node = q('[data-escore-preview="' + name + '"]');
                var text = form.elements[name].value, commentBaseline;
                if (imageCover && name === 'title') text = text.toUpperCase();
                if (imageCover && name === 'bottom_text' && text === 'PianoLIT eScore') text = 'PianoLIT\neScore';
                if (imageCover && name === 'comment') {
                    commentBaseline = 185 + (subtitleLayout.text ? subtitleLayout.text.split('\n').length : 0) * subtitleLayout.size * 1.2 + 1.2;
                    spec[2] = Math.max(20, 350 - commentBaseline);
                }
                var layout = fitText(text, spec[0], spec[1], spec[2], function (text, size) {
                    context.font = (imageCover && name === 'subtitle' ? 'bold ' : '') + size + 'px "Escore Bodoni"';
                    return context.measureText(text).width;
                });
                if (name === 'subtitle') subtitleLayout = layout;
                if (imageCover) {
                    var lineCount = layout.text ? layout.text.split('\n').length : 0;
                    var baseline = name === 'title' ? 130 - Math.max(0, lineCount - 1) * layout.size * 1.2 : (name === 'subtitle' ? 185 : (name === 'comment' ? commentBaseline : 734 - Math.max(0, lineCount - 2) * layout.size * 1.2));
                    if (name === 'bottom_text') q('.escore-cover-preview__brand').style.top = ((baseline - layout.size) / 792 * 100) + '%';
                    else node.style.top = ((baseline - layout.size) / 792 * 100) + '%';
                    cover.classList.toggle('escore-cover-preview--no-brand', form.elements.bottom_text.value === '');
                } else if (name === 'comment') node.style.top = ((335 + Math.max(1, subtitleLayout.text.split('\n').length) * subtitleLayout.size * 1.2 + 6 - layout.size) / 792 * 100) + '%';
                node.textContent = layout.text;
                node.style.fontSize = (layout.size / 612 * 100) + 'cqw';
            });
            qa('[data-escore-color]').forEach(function (button) {
                var active = button.dataset.escoreColor === color;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', String(active));
            });
            q('[data-escore-notes]').hidden = !form.elements.include_edition.checked;
        }
        function refreshSelection() {
            var ids = selectedIds(form), count = ids.length, availableRows = qa('[data-escore-piece]').filter(function (row) { return !row.hidden; });
            q('[data-escore-count]').textContent = count;
            q('[data-escore-piece-label]').textContent = count === 1 ? 'piece' : 'pieces';
            q('[data-escore-selection-count]').textContent = count + ' of ' + availableRows.length + ' selected';
            availableRows.forEach(function (row, index) {
                var position = ids.indexOf(row.dataset.escorePiece);
                row.classList.toggle('selected', position !== -1);
                row.querySelector('[data-escore-row-number]').textContent = index + 1;
            });
            controls();
        }
        function goStep(target) {
            if (downloading) return;
            if (target > step && !valid()) {
                message('Enter a title and select at least one available score.', true);
                if (!form.elements.title.value.trim()) form.elements.title.focus();
                return;
            }
            step = target;
            form.dataset.step = step;
            qa('[data-escore-panel]').forEach(function (panel) { panel.hidden = Number(panel.dataset.escorePanel) !== step; });
            qa('[data-escore-step]').forEach(function (button) {
                var number = Number(button.dataset.escoreStep);
                button.classList.toggle('active', number === step);
                button.classList.toggle('complete', number < step);
                if (number === step) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
                button.querySelector('[data-step-number]').hidden = number < step;
                button.querySelector('[data-step-check]').hidden = number >= step;
            });
            q('[data-escore-back]').hidden = step === 1;
            q('[data-escore-back-label]').textContent = step === 3 ? 'Back to pieces' : 'Back to cover';
            q('[data-escore-next-label]').textContent = step === 1 ? 'Continue to pieces' : (step === 2 ? 'Continue to generate' : 'Generate eScore');
            q('[data-escore-next-icon]').hidden = step === 3;
            next.setAttribute('aria-label', q('[data-escore-next-label]').textContent);
            q('[data-escore-back]').setAttribute('aria-label', q('[data-escore-back-label]').textContent);
            q('[data-escore-thumbnail-panel]').hidden = step === 3;
            q('[data-escore-summary]').hidden = step !== 3;
            q('[data-escore-preview-caption]').textContent = step === 2 ? 'See how your selected pieces will look in the eScore.' : "Here’s how your eScore will look.";
            q('.escore-body').scrollTop = 0;
            q('.escore-controls').scrollTop = 0;
            pageNumber = step === 2 ? (metadata ? 1 + metadata.sections.cover + metadata.sections.title : 2) : 1;
            controls();
            renderPages();
        }
        function payload(preview) {
            var data = new FormData(form);
            toggles.forEach(function (name) { data.set(name, form.elements[name].checked ? '1' : '0'); });
            selectedIds(form).forEach(function (id) { data.append('piece_ids[]', id); });
            data.set('preview', preview ? '1' : '0');
            return data;
        }
        async function request(preview, signal, data) {
            var response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin', signal: signal,
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.elements._token.value}, body: data || payload(preview)
            });
            var json = (response.headers.get('content-type') || '').indexOf('application/json') !== -1;
            if (!response.ok || !json && preview || response.redirected) {
                var error = json ? await response.json() : {};
                throw new Error(error.message || 'Your eScore could not be generated. Please try again.');
            }
            return preview ? response.json() : response.blob();
        }
        function invalidate() {
            revision++;
            renderRevision++;
            clearTimeout(timer);
            if (controller) controller.abort();
            metadata = null;
            q('[data-escore-retry]').hidden = true;
            q('[data-escore-total]').textContent = '— pages';
            q('[data-escore-summary-label]').textContent = selectedIds(form).length + ' pieces · Updating pages';
            if (step === 1 || step === 3) {
                mainCanvas.hidden = true; cover.hidden = false;
                q('[data-escore-main-page]').classList.add('escore-book');
            }
            refreshCover(); refreshSelection();
            message(valid() ? 'Updating preview…' : 'Select at least one available score and enter a title.', !valid());
            if (visible && valid()) timer = setTimeout(loadPreview, 650);
        }
        async function loadPreview() {
            clearTimeout(timer);
            if (!visible) return;
            if (!valid()) { message('Select at least one available score and enter a title.', true); controls(); return; }
            if (controller) controller.abort();
            controller = new AbortController();
            var token = ++revision, signal = controller.signal;
            message('Preparing your preview…');
            try {
                var result = await request(true, signal);
                if (token !== revision || !visible) return;
                if (!window.pdfjsLib) throw new Error('The PDF preview could not load. Please try again.');
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.worker.min.js';
                var bytes = Uint8Array.from(atob(result.pdf), function (character) { return character.charCodeAt(0); });
                var loaded = await window.pdfjsLib.getDocument({data: bytes}).promise;
                if (token !== revision || !visible) { loaded.destroy(); return; }
                if (doc) doc.destroy();
                doc = loaded; metadata = result; delete metadata.pdf;
                imageCache = {}; thumbnailLimit = 24;
                pageNumber = Math.min(pageNumber, doc.numPages);
                await renderPages();
                if (token !== revision || !visible) return;
                renderSummary(); renderThumbnails();
                message('Preview ready · ' + metadata.pages + ' pages');
                q('[data-escore-retry]').hidden = true;
                controls();
            } catch (error) {
                if (token !== revision || error.name === 'AbortError' || !visible) return;
                metadata = null; controls();
                message(error.message || 'The preview could not be loaded. Please try again.', true);
                q('[data-escore-retry]').hidden = false;
            }
        }
        async function pageCanvas(number, width) {
            var documentForRender = doc, page = await documentForRender.getPage(number);
            var viewport = page.getViewport({scale: 1}), canvas = document.createElement('canvas');
            viewport = page.getViewport({scale: width / viewport.width});
            canvas.width = Math.round(viewport.width); canvas.height = Math.round(viewport.height);
            await page.render({canvasContext: canvas.getContext('2d'), viewport: viewport}).promise;
            return canvas;
        }
        async function pageImage(number) {
            if (!imageCache[number]) imageCache[number] = pageCanvas(number, 260).then(function (canvas) { return canvas.toDataURL('image/png'); });
            return imageCache[number];
        }
        function activePreviews() {
            qa('[data-escore-thumbnail]').forEach(function (button) { button.classList.toggle('active', Number(button.dataset.escoreThumbnail) === pageNumber); });
            var tab = pageNumber === 1 ? 'cover' : (metadata && pageNumber <= metadata.sections.cover + metadata.sections.title + metadata.sections.index ? 'index' : 'pieces');
            qa('[data-escore-preview-tab]').forEach(function (button) {
                var active = button.dataset.escorePreviewTab === tab;
                button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active));
            });
        }
        async function renderPages() {
            if (!doc || !metadata) return;
            var token = ++renderRevision, version = revision;
            activePreviews();
            q('[data-escore-page-label]').textContent = pageNumber + ' / ' + doc.numPages;
            q('[data-escore-previous-page]').disabled = pageNumber <= 1;
            q('[data-escore-next-page]').disabled = pageNumber >= doc.numPages;
            try {
                var page = await pageCanvas(pageNumber, Math.min(1200, Math.max(600, q('[data-escore-main-page]').clientWidth * (window.devicePixelRatio || 1))));
                if (token !== renderRevision || version !== revision || !visible) return;
                mainCanvas.width = page.width; mainCanvas.height = page.height;
                mainCanvas.getContext('2d').drawImage(page, 0, 0);
                mainCanvas.hidden = false;
                cover.hidden = true;
                q('[data-escore-main-page]').classList.toggle('escore-book', pageNumber === 1);
                q('[data-escore-main-page]').style.aspectRatio = page.width + ' / ' + page.height;
            } catch (error) {
                if (version === revision && visible) message('This preview page could not be displayed. Try refreshing the preview.', true);
            }
        }
        async function renderThumbnails() {
            var target = q('[data-escore-thumbnails]'), version = revision;
            target.replaceChildren();
            // Bound rendering work for large folders; all pages remain available via the pager.
            for (var number = 1; number <= Math.min(doc.numPages, thumbnailLimit); number++) {
                var button = document.createElement('button'), image = document.createElement('img'), label = document.createElement('span');
                button.type = 'button'; button.className = 'escore-thumbnail'; button.dataset.escoreThumbnail = number;
                button.setAttribute('aria-label', 'Preview page ' + number);
                image.alt = ''; label.textContent = number; button.append(image, label); target.append(button);
                button.addEventListener('click', function (event) { pageNumber = Number(event.currentTarget.dataset.escoreThumbnail); renderPages(); });
                try {
                    image.src = await pageImage(number);
                    if (version !== revision || !visible) return;
                } catch (error) { if (version !== revision) return; }
            }
            if (thumbnailLimit < doc.numPages) {
                var more = document.createElement('button'); more.type = 'button'; more.className = 'btn btn-secondary btn-sm'; more.textContent = 'More pages';
                more.addEventListener('click', function () { thumbnailLimit += 24; renderThumbnails(); }); target.append(more);
            }
            activePreviews();
        }
        function renderSummary() {
            var target = q('[data-escore-summary-pieces]'), breakdown = q('[data-escore-breakdown]'), version = revision;
            target.replaceChildren(); breakdown.replaceChildren();
            if (summaryObserver) summaryObserver.disconnect();
            summaryObserver = typeof IntersectionObserver !== 'undefined' ? new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting || version !== revision) return;
                    var image = entry.target; summaryObserver.unobserve(image);
                    pageImage(Number(image.dataset.page)).then(function (src) { if (version === revision) image.src = src; }).catch(function () {});
                });
            }, {rootMargin: '100px'}) : null;
            q('[data-escore-summary-label]').textContent = metadata.entries.length + ' pieces · ' + metadata.pages + ' pages';
            q('[data-escore-total]').textContent = metadata.pages + ' pages';
            metadata.entries.forEach(function (entry, index) {
                var row = document.createElement('div'), image = document.createElement('img'), info = document.createElement('div'), title = document.createElement('div'), detail = document.createElement('small');
                row.className = 'escore-summary-row'; image.alt = '';
                title.textContent = (index + 1) + '. ' + entry.title;
                detail.textContent = entry.composer + ' · ' + entry.pages + (entry.pages === 1 ? ' page' : ' pages');
                info.append(title, detail); row.append(image, info); target.append(row);
                image.dataset.page = entry.start;
                if (summaryObserver) summaryObserver.observe(image);
                else if (index < 24) pageImage(entry.start).then(function (src) { if (version === revision) image.src = src; }).catch(function () {});
            });
            var sections = metadata.sections;
            var rows = [['Cover page', sections.cover], ['Title page', sections.title], ['Table of contents', sections.index], ['About this edition', sections.edition], ['Blank pages', sections.blank], [metadata.entries.length + ' pieces', metadata.entries.reduce(function (total, entry) { return total + entry.pages; }, 0)]];
            rows.filter(function (row) { return row[1] > 0; }).forEach(function (values) {
                var row = document.createElement('div'), label = document.createElement('span'), count = document.createElement('span');
                row.className = 'escore-breakdown-row'; label.textContent = values[0]; count.textContent = values[1]; row.append(label, count); breakdown.append(row);
            });
        }
        async function download() {
            if (!metadata || !valid() || downloading) return;
            var data = payload(false);
            downloading = true; controls(); message('Generating your eScore…');
            try {
                var blob = await request(false, undefined, data), url = URL.createObjectURL(blob), anchor = document.createElement('a');
                if (blob.type.indexOf('application/pdf') === -1) { URL.revokeObjectURL(url); throw new Error('Your eScore could not be generated. Please try again.'); }
                anchor.href = url; anchor.download = (form.elements.title.value.replace(/[^\p{L}\p{N}\s._-]/gu, '').trim() || 'PianoLIT eScore') + '.pdf';
                document.body.append(anchor); anchor.click(); anchor.remove(); setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
                message('Your eScore is ready. The download has started.');
            } catch (error) { message(error.message, true); }
            finally { downloading = false; controls(); }
        }
        function moveRow(row, direction) {
            var rows = qa('[data-escore-piece]').filter(function (item) { return !item.hidden; }), index = rows.indexOf(row), neighbor = rows[index + direction];
            if (!neighbor) return;
            if (direction < 0) neighbor.before(row); else neighbor.after(row);
            invalidate();
        }
        qa('[data-escore-color]').forEach(function (button) { button.addEventListener('click', function () { form.elements.color.value = button.dataset.escoreColor; invalidate(); }); });
        qa('[data-escore-step]').forEach(function (button) { button.addEventListener('click', function () { goStep(Number(button.dataset.escoreStep)); }); });
        q('[data-escore-back]').addEventListener('click', function () { goStep(step - 1); });
        next.addEventListener('click', function () { if (step < 3) goStep(step + 1); else download(); });
        q('[data-escore-retry]').addEventListener('click', loadPreview);
        q('[data-escore-previous-page]').addEventListener('click', function () { if (doc && pageNumber > 1) { pageNumber--; renderPages(); } });
        q('[data-escore-next-page]').addEventListener('click', function () { if (doc && pageNumber < doc.numPages) { pageNumber++; renderPages(); } });
        qa('[data-escore-preview-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!metadata) return;
                pageNumber = button.dataset.escorePreviewTab === 'cover' ? 1 : (button.dataset.escorePreviewTab === 'index' ? 1 + metadata.sections.cover + metadata.sections.title : metadata.entries[0].start);
                renderPages();
            });
        });
        form.addEventListener('submit', function (event) { event.preventDefault(); if (step < 3) goStep(step + 1); else download(); });
        form.addEventListener('input', invalidate);
        form.addEventListener('change', function (event) {
            if (event.target.matches('select, [data-escore-select], [type="checkbox"]')) invalidate();
        });
        qa('[data-escore-drag]').forEach(function (handle) {
            handle.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowUp' || event.key === 'ArrowDown') { event.preventDefault(); moveRow(handle.closest('[data-escore-piece]'), event.key === 'ArrowUp' ? -1 : 1); handle.focus(); }
            });
            handle.addEventListener('pointerdown', function (event) {
                if (event.button !== 0 || handle.disabled || downloading || endDrag) return;
                event.preventDefault();
                var row = handle.closest('[data-escore-piece]'), changed = false;
                var pointerId = event.pointerId;
                row.classList.add('dragging');
                function move(event) {
                    if (event.pointerId !== pointerId) return;
                    var hit = document.elementFromPoint(event.clientX, event.clientY), other = hit && hit.closest('[data-escore-piece]');
                    if (!other || other === row || !form.contains(other)) return;
                    var rect = other.getBoundingClientRect();
                    if (event.clientY < rect.top + rect.height / 2) other.before(row); else other.after(row);
                    changed = true; refreshSelection();
                    var controls = q('.escore-controls');
                    var list = controls.scrollHeight > controls.clientHeight ? controls : q('.escore-body');
                    var bounds = list.getBoundingClientRect();
                    if (event.clientY < bounds.top + 35) list.scrollTop -= 15;
                    if (event.clientY > bounds.bottom - 35) list.scrollTop += 15;
                }
                function end(event) {
                    if (event && event.pointerId !== pointerId) return;
                    row.classList.remove('dragging'); document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', end); document.removeEventListener('pointercancel', end);
                    endDrag = null;
                    if (changed) invalidate();
                }
                // Moving the captured handle's row can release pointer capture.
                // Follow the gesture on the document so drop always cleans up.
                document.addEventListener('pointermove', move); document.addEventListener('pointerup', end); document.addEventListener('pointercancel', end);
                endDrag = end;
            });
        });
        // Folder changes may remove or reorder tracks while this editor is closed.
        modal.addEventListener('shown.bs.modal', function () {
            visible = true;
            var playlist = modal.closest('[data-playlist-page]') || document.querySelector('[data-playlist-page]');
            if (playlist) {
                qa('[data-escore-piece]').forEach(function (row) {
                    var track = playlist.querySelector('[data-track][data-piece-id="' + row.dataset.escorePiece + '"]');
                    var duration = track && track.querySelector('[data-track-duration]');
                    if (duration) row.querySelector('[data-escore-duration]').textContent = duration.textContent;
                });
            }
            if (form.dataset.folder === 'true' && playlist) {
                var tracks = Array.from(playlist.querySelectorAll('[data-track][data-piece-id]'));
                if (playlist.querySelector('[data-playlist-tracks]')) {
                    qa('[data-escore-piece]').forEach(function (row) { row.hidden = !tracks.some(function (track) { return track.dataset.pieceId === row.dataset.escorePiece; }); });
                    tracks.forEach(function (track) {
                        var row = qa('[data-escore-piece]').find(function (row) { return row.dataset.escorePiece === track.dataset.pieceId; });
                        if (row && !metadata) q('[data-escore-piece-list]').append(row);
                    });
                }
            }
            refreshSelection(); refreshCover(); loadPreview();
        });
        modal.addEventListener('hidden.bs.modal', function () {
            visible = false; revision++; renderRevision++; clearTimeout(timer);
            if (endDrag) endDrag();
            if (controller) controller.abort();
            if (doc) { doc.destroy(); doc = null; }
            if (summaryObserver) summaryObserver.disconnect();
            metadata = null; imageCache = {}; controls();
        });
        refreshCover(); refreshSelection(); goStep(1);
        if (document.fonts) document.fonts.ready.then(refreshCover);
    }
    function init() { document.querySelectorAll('[data-escore-form]').forEach(initWizard); }
    if (typeof module !== 'undefined') module.exports = {textColor: textColor, fitText: fitText, selectedIds: selectedIds, initWizard: initWizard};
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
    }
}());
