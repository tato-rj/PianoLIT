(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else api.initialize(root.jQuery, root);
})(typeof window !== 'undefined' ? window : this, function () {
    const naturals = {c: 0, d: 2, e: 4, f: 5, g: 7, a: 9, b: 11};
    const tones = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    function human(note) { return note.replace(/2/g, '').replace(/\+/g, '#').replace(/-/g, 'b'); }
    function machine(note) { return note[0].toUpperCase() + note.slice(1).replace(/#/g, '+').replace(/b/g, '-'); }
    function pitch(note) {
        const match = /^([a-g])([#b]*)$/i.exec(human(note));
        if (!match) return null;
        return (naturals[match[1].toLowerCase()] + Array.from(match[2]).reduce(function (sum, accidental) { return sum + (accidental === '#' ? 1 : -1); }, 0) + 12) % 12;
    }
    function voice(notes, bass) {
        const bassIndex = bass ? notes.findIndex(function (note) { return pitch(note) === pitch(bass); }) : -1;
        if (bassIndex > 0) notes = notes.slice(bassIndex).concat(notes.slice(0, bassIndex));
        let previous = -1;
        return notes.map(function (note) {
            const value = pitch(note);
            if (value === null) return null;
            let midi = 48 + value; // Begin in octave 3 and ascend through the supplied inversion.
            while (midi <= previous) midi += 12;
            previous = midi;
            return {name: human(note), pitch: value, octave: Math.floor(midi / 12) - 1, tone: tones[value] + (Math.floor(midi / 12) - 1)};
        });
    }
    function spellings(notes) {
        // Suggest conventional names for common pitch shapes, using only the
        // keyboard's existing aliases. Explicit user spellings bypass this.
        const shapes = [
            [[0, 4, 7], [0, 2, 4]], [[0, 3, 7], [0, 2, 4]],
            [[0, 3, 6], [0, 2, 4]], [[0, 4, 8], [0, 2, 4]],
            [[0, 2, 7], [0, 1, 4]], [[0, 5, 7], [0, 3, 4]],
            [[0, 4, 7, 10], [0, 2, 4, 6]], [[0, 4, 7, 11], [0, 2, 4, 6]],
            [[0, 3, 7, 10], [0, 2, 4, 6]], [[0, 3, 7, 11], [0, 2, 4, 6]],
            [[0, 3, 6, 10], [0, 2, 4, 6]], [[0, 3, 6, 9], [0, 2, 4, 6]],
            [[0, 4, 8, 10], [0, 2, 4, 6]], [[0, 4, 8, 11], [0, 2, 4, 6]],
            [[0, 4, 7, 9], [0, 2, 4, 5]], [[0, 3, 7, 9], [0, 2, 4, 5]],
            [[0, 5, 7, 10], [0, 3, 4, 6]],
            [[0, 4, 7, 10, 2], [0, 2, 4, 6, 1]],
            [[0, 4, 7, 11, 2], [0, 2, 4, 6, 1]],
            [[0, 3, 7, 10, 2], [0, 2, 4, 6, 1]]
        ];
        const letters = 'cdefgab';
        let best = notes.map(function (note) { return note.name; }), score = Infinity;
        notes.forEach(function (rootNote, rootIndex) {
            rootNote.aliases.forEach(function (rootName) {
                shapes.filter(function (shape) { return shape[0].length === notes.length; }).forEach(function (shape) {
                    const names = notes.map(function (note) {
                        const interval = (pitch(note.name) - pitch(rootName) + 12) % 12;
                        const degree = shape[0].indexOf(interval);
                        if (degree === -1) return null;
                        const letter = letters[(letters.indexOf(rootName[0]) + shape[1][degree]) % 7];
                        return note.aliases.find(function (alias) { return alias[0] === letter; }) || null;
                    });
                    if (names.some(function (name) { return !name; })) return;
                    const cost = names.reduce(function (sum, name) { return sum + name.length - 1; }, 0) * 10 + rootIndex / notes.length + (rootName === rootNote.name ? 0 : 0.01);
                    if (cost < score) { score = cost; best = names; }
                });
            });
        });
        return best;
    }
    function initialize($, win) {
        $(function () {
            const $container = $('#notes-container');
            if (!$container.length) return;
            const $inputPiano = $('#chord-input-piano');
            const $inputKeys = $inputPiano.find('.keyboard-key');
            const $submit = $('#submit-notes');
            const submitHtml = $submit.html();
            const $results = $('#chord-results');
            const selectedKeys = new Map();
            let requestVersion = 0, pending = null, timers = [], playing = false;

            function mode() { return $('#chord-input-tabs .active').attr('href') === '#chord-note-names' ? 'button' : 'keyboard'; }
            function keyboardNotes() { return Array.from(selectedKeys.values()).sort(function (a, b) { return a.index - b.index; }); }
            function notes() {
                if (mode() === 'keyboard') return keyboardNotes().map(function (item) { return machine(item.spelling); });
                return $('.chord-note').toArray().filter(function (el) { return $(el).find('.chord-note-toggle').attr('aria-pressed') === 'true'; })
                    .map(function (el) { const steps = Number(el.dataset.steps); return el.dataset.letter + (steps > 0 ? '+'.repeat(steps) : '-'.repeat(-steps)); });
            }
            function error(message) { $('#chord-error').text(message || '').prop('hidden', !message); }
            function audioStatus(message) {
                $('#chord-audio-status, #chord-result-audio-status').empty().prop('hidden', true);
                const $status = $results.find('#chord-result-audio-status');
                ($status.length ? $status : $('#chord-audio-status')).text(message || '').prop('hidden', !message);
            }
            function stopPlayback() {
                timers.forEach(clearTimeout); timers = [];
                if (playing && win.piano && typeof win.piano.releaseAll === 'function') win.piano.releaseAll();
                playing = false;
                $('[data-chord-stop]').prop('disabled', true);
            }
            function invalidate() {
                requestVersion++;
                if (pending) { pending.cancel(); pending = null; }
                stopPlayback();
                $results.empty().attr('aria-busy', 'false');
                $submit.prop('disabled', false).html(submitHtml);
                error('');
            }
            function pill(target, note) {
                const el = document.getElementById('chord-note-pill').content.firstElementChild.cloneNode(true);
                el.querySelector('[data-note-label]').textContent = note;
                $(target).append(el);
            }
            function renderSpellings() {
                const $options = $('#options-buttons').empty();
                keyboardNotes().forEach(function (item) {
                    const field = document.getElementById('chord-spelling-template').content.firstElementChild.cloneNode(true);
                    const select = field.querySelector('select');
                    const id = 'chord-spelling-' + pitch(item.spelling);
                    select.id = id; select.name = id; select.dataset.pitch = String(pitch(item.spelling));
                    const label = field.querySelector('label');
                    label.htmlFor = id;
                    // Preserve the note letter's case while keeping a flat accidental lowercase.
                    label.querySelector('small').textContent = item.key.dataset.name[0].toUpperCase() + item.key.dataset.name.slice(1);
                    JSON.parse(item.key.dataset.names).forEach(function (name) {
                        const option = document.createElement('option');
                        option.value = name; option.textContent = name[0].toUpperCase() + name.slice(1);
                        option.selected = name === item.spelling;
                        select.append(option);
                    });
                    $options.append(field);
                });
                $('#options-container').prop('hidden', !selectedKeys.size || mode() !== 'keyboard');
            }
            function renderSelection(rebuildSpellings) {
                const keys = keyboardNotes();
                if (mode() === 'keyboard' && !keys.some(function (item) { return item.explicit; })) {
                    const names = spellings(keys.map(function (item) { return {name: item.key.dataset.name, aliases: JSON.parse(item.key.dataset.names)}; }));
                    keys.forEach(function (item, index) { item.spelling = names[index]; });
                }
                const current = notes(), root = $('#chord-root').val();
                $('#chord-selected-notes').empty();
                current.forEach(function (note) { pill('#chord-selected-notes', human(note)); });
                $('#chord-selection-count').text(current.length ? current.length + (current.length === 1 ? ' note selected' : ' notes selected') : 'No notes selected');
                const $root = $('#chord-root').empty().append($('<option>').val('').text('Auto detect'));
                current.forEach(function (note) { $root.append($('<option>').val(note).text(human(note))); });
                $root.val(current.includes(root) ? root : '');
                $('#root-container').prop('hidden', current.length < 3);
                $('#chord-options').prop('hidden', mode() === 'keyboard' ? !current.length : current.length < 3);
                $('#chord-options-panel-heading .accordion-button').text(mode() === 'keyboard' ? 'Note spelling & root (optional)' : 'Root note (optional)');
                $inputKeys.each(function () {
                    const selected = selectedKeys.get(pitch(this.dataset.name));
                    const active = !!selected && selected.key === this;
                    $(this).attr('aria-pressed', String(active)).children('.dot').toggle(active);
                });
                if (rebuildSpellings !== false) renderSpellings();
            }
            function readyAudio() {
                if (!win.piano || !win.piano.loaded) { audioStatus('The piano is still loading. Please try playback again in a moment.'); return false; }
                audioStatus('');
                return true;
            }
            function resumeAudio() {
                if (win.Tone && win.Tone.context && typeof win.Tone.context.resume === 'function') {
                    win.Tone.context.resume().catch(function () { stopPlayback(); audioStatus('The piano could not start. Please try playback again.'); });
                }
            }
            function toggleKey(key) {
                const value = pitch(key.dataset.name);
                const selected = selectedKeys.get(value);
                if (selected && selected.key === key) selectedKeys.delete(value);
                else selectedKeys.set(value, {key: key, index: $inputKeys.index(key), spelling: selected ? selected.spelling : key.dataset.name, explicit: selected ? selected.explicit : false});
                invalidate(); renderSelection();
            }
            $inputKeys.each(function () {
                $(this).attr({role: 'button', tabindex: '0', 'aria-label': 'Select ' + human(this.dataset.name).toUpperCase() + this.dataset.octave, 'aria-pressed': 'false'});
            });
            $inputPiano.on('click', function (event) {
                const key = $(event.target).closest('.keyboard-key')[0];
                if (key) toggleKey(key);
            }).on('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                const key = $(event.target).closest('.keyboard-key')[0];
                if (!key) return;
                event.preventDefault(); event.stopPropagation();
                toggleKey(key); resumeAudio();
                if (readyAudio()) win.piano.triggerAttackRelease(key.dataset.name.toUpperCase() + key.dataset.octave, '8n');
            });
            $('#chord-editor').on('pointerdown', '.keyboard-key', function () { stopPlayback(); resumeAudio(); });
            $container.on('mousedown touchstart', function (event) {
                if (!$(event.target).closest('.keyboard-key').length) return;
                if (!readyAudio()) event.stopPropagation();
                resumeAudio();
            });
            $('#options-buttons').on('change', 'select', function () {
                const item = selectedKeys.get(Number(this.dataset.pitch));
                if (!item) return;
                item.spelling = this.value;
                item.explicit = true;
                invalidate(); renderSelection(false);
            });
            $('.chord-note-toggle').on('click', function () {
                const active = this.getAttribute('aria-pressed') !== 'true';
                $(this).attr('aria-pressed', String(active)).toggleClass('btn-primary', active).toggleClass('btn-secondary', !active);
                updateNote($(this).closest('.chord-note'));
            });
            $('.chord-note [data-step]').on('click', function () {
                const $note = $(this).closest('.chord-note');
                $note.attr('data-steps', Number($note.attr('data-steps')) + Number(this.dataset.step));
                updateNote($note);
            });
            function updateNote($note) {
                const steps = Number($note.attr('data-steps')), active = $note.find('.chord-note-toggle').attr('aria-pressed') === 'true';
                $note.find('[data-accidental-label]').text(steps > 0 ? '♯'.repeat(steps) : '♭'.repeat(-steps));
                $note.find('[data-step="-1"]').prop('disabled', !active || steps <= -2);
                $note.find('[data-step="1"]').prop('disabled', !active || steps >= 2);
                invalidate(); renderSelection(); resumeAudio();
                if (active && readyAudio()) {
                    const value = pitch($note.attr('data-letter') + (steps > 0 ? '+'.repeat(steps) : '-'.repeat(-steps)));
                    win.piano.triggerAttackRelease(tones[value] + $note.attr('data-octave'), '8n');
                }
            }
            $('#chord-root').on('change', invalidate);
            $('#chord-input-tabs [data-bs-toggle="tab"]').on('shown.bs.tab', function () { invalidate(); renderSelection(); });
            $('#chord-clear').on('click', function () {
                selectedKeys.clear();
                $('.chord-note').attr('data-steps', '0').find('[data-accidental-label]').empty();
                $('.chord-note-toggle').attr('aria-pressed', 'false').removeClass('btn-primary').addClass('btn-secondary');
                $('.chord-note [data-step]').prop('disabled', true);
                invalidate(); renderSelection(); audioStatus('');
            });
            $('#chord-example').on('click', function () {
                const example = function () {
                    selectedKeys.clear();
                    ['c', 'e', 'g'].forEach(function (name) {
                        const key = $inputKeys.filter('[data-name="' + name + '"]').first()[0];
                        selectedKeys.set(pitch(name), {key: key, index: $inputKeys.index(key), spelling: name});
                    });
                    invalidate(); renderSelection(); submit();
                };
                if (mode() === 'keyboard') example();
                else $('#chord-input-tabs-chord-keyboard').one('shown.bs.tab', example).tab('show');
            });
            $submit.on('click', submit);
            function submit() {
                const selected = notes();
                if (new Set(selected.map(pitch)).size < 3) { error('Choose at least three different notes to find a chord.'); return; }
                invalidate();
                const version = requestVersion;
                pending = win.axios.CancelToken.source();
                $submit.prop('disabled', true).text('Finding chords…');
                $results.attr('aria-busy', 'true');
                win.axios.get($container.attr('data-url'), {params: {notes: selected, root: $('#chord-root').val() || null, tool: mode()}, cancelToken: pending.token, timeout: 20000})
                    .then(function (response) {
                        if (version !== requestVersion) return;
                        $results.html(response.data);
                        chooseChord($results.find('.chords-results button').first(), false);
                        const heading = document.getElementById('chord-results-title');
                        if (heading) { heading.focus({preventScroll: true}); heading.scrollIntoView({behavior: 'auto', block: 'start'}); }
                    }).catch(function (failure) {
                        if (version !== requestVersion || win.axios.isCancel(failure)) return;
                        const data = failure.response && failure.response.data;
                        error(failure.response && failure.response.status === 422 && data && typeof data.message === 'string' ? data.message : 'We couldn’t check those notes. Please try again.');
                    }).finally(function () {
                        if (version !== requestVersion) return;
                        pending = null; $submit.prop('disabled', false).html(submitHtml); $results.attr('aria-busy', 'false');
                    });
            }
            function chooseChord($button, play) {
                if (!$button.length) return;
                stopPlayback();
                $results.find('.chords-results button').removeClass('btn-primary').addClass('btn-secondary').attr('aria-pressed', 'false');
                $button.removeClass('btn-secondary').addClass('btn-primary').attr('aria-pressed', 'true');
                $results.find('.chord-info').hide();
                $(document.getElementById($button.attr('aria-controls'))).show();
                $('#chord-playing-title').text($button.text().trim());
                const voiced = voice(JSON.parse($button.attr('data-notes')), $button.attr('data-bass')).filter(Boolean);
                const $keys = $('#chord-output-piano .keyboard-key');
                $keys.children('.dot').hide(); $('#chord-label').empty();
                voiced.forEach(function (note) {
                    pill('#chord-label', note.name[0].toUpperCase() + note.name.slice(1));
                    let $key = $keys.filter(function () { return pitch(this.dataset.name) === note.pitch && Number(this.dataset.octave) === note.octave; }).first();
                    if (!$key.length) $key = $keys.filter(function () { return pitch(this.dataset.name) === note.pitch; }).first();
                    $key.children('.dot').show();
                });
                if (!play || !readyAudio()) return;
                resumeAudio(); playing = true; $('[data-chord-stop]').prop('disabled', false);
                voiced.forEach(function (note, index) { timers.push(setTimeout(function () { win.piano.triggerAttackRelease(note.tone, '8n'); }, index * 220)); });
                timers.push(setTimeout(function () { win.piano.triggerAttackRelease(voiced.map(function (note) { return note.tone; }), '1n'); }, voiced.length * 220 + 100));
                timers.push(setTimeout(stopPlayback, voiced.length * 220 + 2100));
            }
            $results.on('click', '.chords-results button', function () { chooseChord($(this), true); });
            $results.on('click', '[data-chord-stop]', stopPlayback);
            $results.on('click', '[data-chord-edit]', function (event) {
                event.preventDefault();
                stopPlayback(); document.getElementById('chord-editor-title').scrollIntoView({block: 'start'}); $('#chord-clear').trigger('focus');
            });
            $results.on('pointerdown', '.keyboard-key', function () { stopPlayback(); resumeAudio(); });
            $(win).on('pagehide', stopPlayback);
            $(document).on('visibilitychange', function () { if (document.hidden) stopPlayback(); });
            $(document).on('show.bs.modal show.bs.offcanvas', stopPlayback);
            renderSelection();
        });
    }
    return {human: human, machine: machine, pitch: pitch, voice: voice, spellings: spellings, initialize: initialize};
});
