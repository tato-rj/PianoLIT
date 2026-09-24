(function (root) {
    'use strict';
    const clamp = value => Math.max(0, Math.min(1, value));
    const point = (event, bounds) => ({x: clamp((event.clientX - bounds.left) / bounds.width), y: clamp((event.clientY - bounds.top) / bounds.height)});
    const roundPoint = p => ({x: Math.round(p.x * 100000) / 100000, y: Math.round(p.y * 100000) / 100000});

    class Markings {
        constructor(save, changed) {
            this.save = save;
            this.changed = changed || function () {};
            this.marks = [];
            this.undoStack = [];
            this.redoStack = [];
            this.revision = 0;
            this.edits = this.savedEdits = 0;
            this.saving = false;
            this.pending = null;
            this.conflict = false;
            this.state = 'loading';
        }
        load(data) {
            if (!data || !Array.isArray(data.marks) || !Number.isInteger(data.revision) || data.revision < 0) throw new Error('Invalid saved markings');
            this.marks = data.marks;
            this.revision = data.revision;
            this.undoStack = []; this.redoStack = [];
            this.edits = this.savedEdits = 0;
            this.pending = null; this.conflict = false;
            this.state = 'saved'; this.changed();
        }
        get dirty() { return this.edits !== this.savedEdits || this.pending !== null; }
        replace(marks, checkpoint = true) {
            if (checkpoint) this.undoStack.push(this.marks);
            if (this.undoStack.length > 100) this.undoStack.shift();
            this.redoStack = [];
            this.marks = marks; this.edits++; this.state = 'unsaved'; this.changed();
        }
        undo() {
            if (!this.undoStack.length) return;
            this.redoStack.push(this.marks); this.marks = this.undoStack.pop();
            this.edits++; this.state = 'unsaved'; this.changed();
        }
        redo() {
            if (!this.redoStack.length) return;
            this.undoStack.push(this.marks); this.marks = this.redoStack.pop();
            this.edits++; this.state = 'unsaved'; this.changed();
        }
        async flush() {
            if (this.saving || this.conflict || !this.dirty) return;
            this.saving = true;
            this.pending = this.pending || {marks: this.marks, revision: this.revision, edits: this.edits};
            const pending = this.pending;
            this.state = 'saving'; this.changed();
            let succeeded = false;
            try {
                const result = await this.save({marks: pending.marks, revision: pending.revision});
                if (!result || result.revision !== pending.revision + 1) throw new Error('Save was not acknowledged');
                this.revision = result.revision;
                this.savedEdits = pending.edits;
                this.pending = null;
                this.state = this.dirty ? 'unsaved' : 'saved';
                succeeded = true;
            } catch (error) {
                this.conflict = !!(error.response && error.response.status === 409);
                this.state = this.conflict ? 'conflict' : 'error';
            } finally {
                this.saving = false; this.changed();
            }
            if (succeeded && this.dirty) await this.flush();
        }
    }

    class Editor {
        constructor(container, pdfjs, http) {
            this.root = container; this.pdfjs = pdfjs; this.http = http;
            this.canvas = this.find('canvas'); this.svg = this.find('svg');
            this.sheet = this.find('.score-sheet'); this.scroller = this.find('.score-scroll');
            this.status = this.find('[data-score-status]');
            this.tool = 'read'; this.page = 1; this.zoom = 1;
            this.ready = false; this.rendering = false; this.renderId = 0;
            this.stroke = null; this.pointerId = null; this.pdf = null; this.textDraft = null;
            this.store = new Markings(data => this.http.put(this.url, Object.assign({}, this.identity, data)).then(response => response.data), () => this.changed());
            this.url = container.getAttribute('data-annotations-url');
            this.bind(); this.start();
        }
        find(selector) { return this.root.querySelector(selector); }
        all(selector) { return this.root.querySelectorAll(selector); }
        async start() {
            this.ready = false; this.message('Loading score\u2026'); this.controls();
            try {
                this.pdf = this.pdf || await this.pdfjs.getDocument({url: this.root.getAttribute('data-pdf-url')}).promise;
                if (this.pdf.numPages > 2000) throw new Error('Too many pages');
                this.identity = {score: this.root.getAttribute('data-score-version'), fingerprint: this.pdf.fingerprint};
                await this.render(this.page);
                const response = await this.http.get(this.url, {params: this.identity});
                this.store.load(response.data);
                this.ready = true; this.changed();
            } catch (error) {
                this.message('Your score or saved markings could not be loaded. Retry to begin editing.', true);
                this.find('[data-retry-load]').hidden = false;
                this.controls();
            }
        }
        message(text, error) {
            this.status.textContent = text;
            this.status.classList.toggle('text-danger', !!error);
        }
        changed() {
            this.paint(); this.controls();
            const labels = {loading: 'Loading your markings\u2026', saved: 'All markings saved', unsaved: 'Unsaved changes\u2026', saving: 'Saving\u2026', error: 'Could not save. Your changes are still here. Retry before leaving.', conflict: 'The score or markings changed elsewhere. Reload to continue; your changes have not been saved.'};
            this.message(labels[this.store.state], ['error', 'conflict'].includes(this.store.state));
            this.find('[data-retry-save]').hidden = this.store.state !== 'error';
            this.find('[data-reload-score]').hidden = !this.store.conflict;
            clearTimeout(this.saveTimer);
            if (this.store.state === 'unsaved') this.saveTimer = setTimeout(() => this.store.flush(), 650);
        }
        controls() {
            const editing = this.ready && !this.rendering && !this.store.conflict;
            if (this.textDraft) this.textDraft.input.readOnly = this.store.conflict;
            this.all('[data-edit-control]').forEach(el => { el.disabled = !editing; });
            this.find('.score-color-button').classList.toggle('disabled', !editing);
            this.find('[data-undo]').disabled = !editing || !this.store.undoStack.length;
            this.find('[data-redo]').disabled = !editing || !this.store.redoStack.length;
            this.find('[data-prev]').disabled = !this.pdf || this.rendering || this.page <= 1;
            this.find('[data-next]').disabled = !this.pdf || this.rendering || this.page >= this.pdf.numPages;
            this.all('[data-zoom]').forEach(el => { el.disabled = !this.pdf || this.rendering; });
            this.svg.style.touchAction = editing && this.tool !== 'read' ? 'none' : 'auto';
            this.svg.setAttribute('data-tool', this.tool);
            this.svg.style.cursor = this.tool === 'read' ? 'auto' : (this.tool === 'text' ? 'text' : 'crosshair');
            this.all('button[data-tool]').forEach(el => {
                const selected = el.getAttribute('data-tool') === this.tool;
                el.classList.toggle('active', selected); el.setAttribute('aria-pressed', String(selected));
            });
        }
        selectTool(tool) {
            const next = this.tool === tool ? 'read' : tool;
            this.finishStroke(); this.finishText();
            this.tool = next; this.controls();
        }
        bind() {
            this.all('button[data-tool]').forEach(el => el.addEventListener('click', () => this.selectTool(el.getAttribute('data-tool'))));
            document.addEventListener('pointerdown', event => {
                if (this.tool === 'read' || this.sheet.contains(event.target)) return;
                const button = event.target.closest('button[data-tool]');
                if (button && this.root.contains(button)) return;
                this.selectTool('read');
            });
            this.find('[data-undo]').addEventListener('click', () => { this.finishText(); this.store.undo(); });
            this.find('[data-redo]').addEventListener('click', () => { this.finishText(); this.store.redo(); });
            this.find('[data-prev]').addEventListener('click', () => this.render(this.page - 1).catch(() => this.renderError()));
            this.find('[data-next]').addEventListener('click', () => this.render(this.page + 1).catch(() => this.renderError()));
            this.all('[data-zoom]').forEach(el => el.addEventListener('click', () => {
                this.zoom = Math.max(0.75, Math.min(2.5, this.zoom + Number(el.getAttribute('data-zoom'))));
                this.render(this.page).catch(() => this.renderError());
            }));
            this.find('[data-retry-load]').addEventListener('click', () => { this.find('[data-retry-load]').hidden = true; if (this.ready) this.render(this.page).catch(() => this.renderError()); else this.start(); });
            this.find('[data-retry-save]').addEventListener('click', () => this.store.flush());
            this.find('[data-reload-score]').addEventListener('click', () => {
                if (root.confirm('Reload the saved score? Your unsaved markings in this tab will be replaced.')) {
                    this.discarding = true; root.location.reload();
                }
            });
            this.svg.addEventListener('pointerdown', event => this.down(event));
            this.svg.addEventListener('pointermove', event => this.move(event));
            this.svg.addEventListener('pointerup', event => { if (event.pointerId === this.pointerId) this.finishStroke(); });
            this.svg.addEventListener('pointercancel', () => { this.stroke = null; this.pointerId = null; this.paint(); });
            this.svg.addEventListener('lostpointercapture', () => this.finishStroke());
            root.addEventListener('beforeunload', event => {
                this.finishStroke(); this.finishText();
                if (this.store.dirty && !this.discarding) { this.store.flush(); event.preventDefault(); event.returnValue = ''; }
            });
            root.addEventListener('online', () => { if (this.ready && this.store.state === 'error') this.store.flush(); });
            root.addEventListener('resize', () => {
                clearTimeout(this.resizeTimer);
                this.resizeTimer = setTimeout(() => { if (this.pdf && !this.rendering) this.render(this.page).catch(() => this.renderError()); }, 200);
            });
        }
        renderError() { this.message('This page could not be displayed. Retry loading the score.', true); this.find('[data-retry-load]').hidden = false; }
        async render(number) {
            if (!this.pdf || number < 1 || number > this.pdf.numPages) return;
            if (this.rendering) { this.queuedPage = number; return; }
            this.finishStroke();
            if (number !== this.page) this.finishText();
            this.rendering = true; this.controls();
            const id = ++this.renderId;
            try {
                const page = await this.pdf.getPage(number);
                const original = page.getViewport({scale: 1});
                const width = Math.max(240, (this.scroller.clientWidth || 680) - 24) * this.zoom;
                const ratio = original.height / original.width;
                // Cap canvas pixels for mobile Safari; the vector ink stays sharp at every zoom.
                const density = Math.min(root.devicePixelRatio || 1, 2, Math.sqrt(7000000 / (width * width * ratio)));
                const viewport = page.getViewport({scale: width / original.width * density});
                const canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width); canvas.height = Math.floor(viewport.height);
                await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
                if (id !== this.renderId) return;
                this.canvas.width = canvas.width; this.canvas.height = canvas.height;
                this.canvas.getContext('2d').drawImage(canvas, 0, 0);
                this.sheet.style.width = width + 'px'; this.sheet.style.height = (width * ratio) + 'px';
                this.ratio = ratio; this.page = number;
                this.svg.setAttribute('viewBox', '0 0 1000 ' + (1000 * ratio));
                this.find('[data-page-label]').textContent = 'Page ' + number + ' of ' + this.pdf.numPages;
                this.find('[data-zoom-label]').textContent = Math.round(this.zoom * 100) + '%';
                this.paint(); this.positionText();
            } finally {
                this.rendering = false; this.controls();
                if (this.queuedPage) {
                    const next = this.queuedPage; this.queuedPage = null;
                    this.render(next).catch(() => this.renderError());
                }
            }
        }
        paint() {
            while (this.svg.firstChild) this.svg.removeChild(this.svg.firstChild);
            if (!this.ratio) return;
            this.store.marks.concat(this.stroke ? [this.stroke] : []).filter(mark => mark.page === this.page && (!this.textDraft || mark.id !== this.textDraft.mark.id)).forEach(mark => {
                const el = document.createElementNS('http://www.w3.org/2000/svg', mark.type === 'text' ? 'text' : 'path');
                el.setAttribute('data-mark-id', mark.id);
                if (mark.type === 'text') {
                    el.setAttribute('x', mark.x * 1000); el.setAttribute('y', mark.y * 1000 * this.ratio);
                    el.setAttribute('font-size', mark.size * 1000); el.setAttribute('font-family', 'Arial, sans-serif');
                    el.setAttribute('font-weight', '600'); el.setAttribute('fill', mark.color);
                    el.textContent = mark.text;
                } else {
                    let points = mark.points;
                    if (points.length === 1) points = [points[0], {x: points[0].x + 0.00001, y: points[0].y}];
                    el.setAttribute('d', points.map((p, index) => (index ? 'L' : 'M') + (p.x * 1000) + ' ' + (p.y * 1000 * this.ratio)).join(' '));
                    el.setAttribute('fill', 'none'); el.setAttribute('stroke', mark.color);
                    el.setAttribute('stroke-width', mark.width * 1000);
                    el.setAttribute('stroke-linecap', 'round'); el.setAttribute('stroke-linejoin', 'round');
                }
                this.svg.appendChild(el);
            });
        }
        down(event) {
            if (!this.ready || this.rendering || this.store.conflict || this.tool === 'read' || this.pointerId !== null || event.button > 0 || event.isPrimary === false) return;
            event.preventDefault();
            this.finishText();
            if (this.tool === 'text') {
                const hit = event.target.closest('[data-mark-id]');
                const existing = hit && this.store.marks.find(mark => mark.id === hit.getAttribute('data-mark-id') && mark.type === 'text');
                if (existing) { this.beginText(existing); return; }
            }
            if (this.tool === 'erase') {
                const hit = event.target.closest('[data-mark-id]');
                if (hit) this.store.replace(this.store.marks.filter(mark => mark.id !== hit.getAttribute('data-mark-id')));
                return;
            }
            if (this.store.marks.length >= 1000) { this.message('This score has reached 1,000 markings. Erase a marking to add another.', true); return; }
            const p = roundPoint(point(event, this.svg.getBoundingClientRect()));
            const mark = {id: 'm' + Date.now().toString(36) + Math.random().toString(36).slice(2), page: this.page, color: this.find('[data-color]').value};
            if (this.tool === 'text') {
                this.beginText(Object.assign(mark, {type: 'text', text: '', size: 0.02, x: p.x, y: p.y}));
            } else {
                const widthControl = this.find('[data-width]');
                const width = widthControl ? Number(widthControl.value) : 0.004;
                this.stroke = Object.assign(mark, {type: 'stroke', width, points: [p]});
                this.pointerId = event.pointerId; this.svg.setPointerCapture(event.pointerId); this.paint();
            }
        }
        beginText(mark) {
            const input = document.createElement('input');
            input.type = 'text'; input.maxLength = 80;
            input.className = 'score-text-input'; input.value = mark.text;
            input.setAttribute('aria-label', 'Text on score');
            input.setAttribute('autocomplete', 'off');
            this.textDraft = {mark: Object.assign({}, mark), input, changed: false};
            input.addEventListener('input', () => { this.syncText(); this.positionText(); });
            input.addEventListener('blur', () => this.finishText());
            input.addEventListener('keydown', event => {
                if (event.key === 'Enter' && !event.isComposing) { event.preventDefault(); input.blur(); }
            });
            this.sheet.appendChild(input); this.paint(); this.positionText();
            // Focus synchronously inside the page click so touch devices open the keyboard.
            input.focus({preventScroll: true});
        }
        positionText() {
            if (!this.textDraft) return;
            const {mark, input} = this.textDraft;
            const width = this.sheet.clientWidth, height = this.sheet.clientHeight;
            const size = mark.size * width;
            const context = this.canvas.getContext('2d');
            context.font = '600 ' + size + 'px Arial';
            const metrics = context.measureText(input.value || ' ');
            const baseline = Number.isFinite(metrics.fontBoundingBoxAscent)
                ? (size * 1.2 - metrics.fontBoundingBoxAscent - metrics.fontBoundingBoxDescent) / 2 + metrics.fontBoundingBoxAscent
                : size * 0.9;
            Object.assign(input.style, {
                left: (mark.x * width) + 'px', top: (mark.y * height - baseline) + 'px',
                fontSize: size + 'px', color: mark.color,
                width: Math.max(size, metrics.width + size * 0.5) + 'px',
                maxWidth: Math.max(size, width * (1 - mark.x)) + 'px'
            });
        }
        syncText() {
            const draft = this.textDraft;
            if (!draft || this.store.conflict) return;
            const text = draft.input.value.trim();
            const previous = this.store.marks.find(mark => mark.id === draft.mark.id);
            if ((previous ? previous.text : '') === text) return;
            const mark = Object.assign({}, draft.mark, {text});
            const marks = this.store.marks.filter(item => item.id !== mark.id);
            if (text) marks.push(mark);
            if (new Blob([JSON.stringify(marks)]).size > 1900000) {
                draft.input.value = previous ? previous.text : '';
                this.message('This score has reached its marking capacity. Erase some markings before adding more.', true);
                return;
            }
            this.store.replace(marks, !draft.changed);
            draft.changed = true;
        }
        finishText() {
            if (!this.textDraft) return;
            this.syncText();
            const input = this.textDraft.input;
            this.textDraft = null;
            input.remove(); this.paint();
        }
        move(event) {
            if (!this.stroke || event.pointerId !== this.pointerId) return;
            event.preventDefault();
            this.stroke.points.push(roundPoint(point(event, this.svg.getBoundingClientRect())));
            if (this.stroke.points.length >= 1500) this.stroke.points = this.stroke.points.filter((p, i) => i % 2 === 0 || i === 1499);
            this.paint();
        }
        finishStroke() {
            if (!this.stroke) return;
            const stroke = this.stroke; const pointerId = this.pointerId;
            this.stroke = null; this.pointerId = null;
            if (this.svg.hasPointerCapture(pointerId)) this.svg.releasePointerCapture(pointerId);
            this.addMark(stroke);
        }
        addMark(mark) {
            const marks = this.store.marks.concat([mark]);
            // Leave room for the request envelope below the server's 2 MB limit.
            if (new Blob([JSON.stringify(marks)]).size > 1900000) {
                this.paint();
                this.message('This score has reached its marking capacity. Erase some markings before adding more.', true);
                return;
            }
            this.store.replace(marks);
        }
    }
    root.ScoreEditor = {Markings, Editor, point, roundPoint};
})(window);
