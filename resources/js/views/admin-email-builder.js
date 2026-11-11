(function (global) {
    'use strict';
    var labels = {text: 'Text', hero: 'Hero', image: 'Image', button: 'Button', panel: 'Panel', columns: 'Two columns', divider: 'Divider', spacer: 'Space'};
    function copy(value) { return JSON.parse(JSON.stringify(value)); }
    function id() { return 'block-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 9); }
    function block(type, settings) {
        return {id: id(), type: type, title: type === 'hero' ? 'Lesson heading' : type === 'panel' ? 'Practice activity' : '',
            content: ['text', 'hero', 'panel', 'columns'].indexOf(type) >= 0 ? '<p>Write your lesson here.</p>' : '',
            content_right: type === 'columns' ? '<p>Add text for the second column.</p>' : '', image: '', alt: '', url: '',
            label: ['button', 'hero', 'panel'].indexOf(type) >= 0 ? 'Read more' : '', background_image: '',
            background: type === 'hero' ? '#eaf0ff' : type === 'panel' ? '#f1eee8' : settings.background,
            color: settings.color, button_color: '#0055fe', padding: type === 'image' || type === 'spacer' ? 0 : 24,
            gap: 16, radius: ['hero', 'panel'].indexOf(type) >= 0 ? 16 : 0, height: 24, image_width: 504,
            align: type === 'hero' || type === 'button' ? 'center' : 'left'};
    }
    function normalize(design) {
        design.settings = Object.assign({background: '#ffffff', color: '#242933', background_image: '', font: 'sans', preheader: ''}, design.settings || {});
        design.blocks = design.blocks.map(function (item) { return Object.assign(block(item.type, design.settings), item); });
        return design;
    }
    function move(items, sourceId, destination) {
        var from = items.findIndex(function (item) { return item.id === sourceId; });
        if (from < 0) return items;
        var next = items.slice();
        var entry = next.splice(from, 1)[0];
        next.splice(Math.max(0, Math.min(next.length, destination > from ? destination - 1 : destination)), 0, entry);
        return next;
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {block: block, normalize: normalize, move: move};
    if (!global.document) return;
    var root = document.querySelector('[data-email-builder]');
    if (!root) return;
    var form = root.closest('form'), field = root.querySelector('[data-design]'), frame = root.querySelector('[data-email-canvas]');
    var status = root.querySelector('[data-builder-status]'), previewStatus = root.querySelector('[data-preview-status]');
    var selected = null, state, history = [], future = [], lastEdit = '', lastEditTime = 0;
    var device = 'desktop';
    var dirty = false, submitting = false, loadingEditors = false, previewMode = false, previewTimer, previewRequest, previewVersion = 0;
    var library = [], uploadTarget = null, uploading = false, uploadVersion = 0, editorReady = {}, editorValues = {}, activeDrag = null;
    var csrf = form.querySelector('[name="_token"]').value;
    var numeric = ['padding', 'gap', 'radius', 'height', 'image_width'];
    var props = ['title', 'content', 'content_right', 'image', 'alt', 'url', 'label', 'background_image', 'background', 'color', 'button_color', 'padding', 'gap', 'radius', 'height', 'image_width', 'align'];
    try {
        state = JSON.parse(field.value);
        if (state.version !== 1 || !Array.isArray(state.blocks)) throw new Error();
        state = normalize(state);
    } catch (error) {
        status.textContent = 'The saved design could not be opened. Keep this page open and reload before editing.';
        return;
    }
    var savedState = JSON.stringify(state), savedSubject = form.querySelector('[name="subject"]').value;
    function say(message) { status.textContent = message; }
    function current() { return state.blocks.find(function (item) { return item.id === selected; }); }
    function element(prop) { return document.getElementById('block-' + prop.replace(/_/g, '-')); }
    function sync() {
        field.value = JSON.stringify(state);
        dirty = root.dataset.hasOldInput === 'true' || field.value !== savedState || form.querySelector('[name="subject"]').value !== savedSubject;
        root.querySelector('[data-builder-action="undo"]').disabled = !history.length;
        root.querySelector('[data-builder-action="redo"]').disabled = !future.length;
        form.querySelector('[data-save-lesson]').disabled = uploading;
    }
    function change(fn, key) {
        var before = JSON.stringify(state);
        fn();
        if (JSON.stringify(state) === before) return;
        if (!key || key !== lastEdit || Date.now() - lastEditTime > 750) {
            history.push(before);
            if (history.length > 60) history.shift();
        }
        lastEdit = key || ''; lastEditTime = Date.now(); future = [];
        sync(); schedulePreview();
        say('Unsaved changes');
    }
    function editorContent(prop) {
        var editor = global.tinymce && global.tinymce.get(element(prop).id);
        var value = editor && editorReady[prop] ? editor.getContent() : element(prop).value;
        // Loading TinyMCE can normalize HTML without a user edit. Keep that out of Undo history.
        return value === editorValues[prop] && current() ? current()[prop] : value;
    }
    function flush() {
        if (!current()) return;
        var content = editorContent('content'), right = editorContent('content_right');
        change(function () { current().content = content; current().content_right = right; }, 'text-' + selected);
    }
    function setEditor(prop, value) {
        element(prop).value = value;
        var editor = global.tinymce && global.tinymce.get(element(prop).id);
        if (editor && editorReady[prop]) editor.setContent(value);
        editorValues[prop] = editor && editorReady[prop] ? editor.getContent() : value;
    }
    function inspector() {
        var item = current();
        root.querySelector('[data-no-selection]').hidden = !!item;
        root.querySelector('[data-block-inspector]').hidden = !item;
        if (!item) { highlight(); return; }
        loadingEditors = true;
        props.forEach(function (prop) {
            if (prop === 'content' || prop === 'content_right') setEditor(prop, item[prop] || '');
            else element(prop).value = item[prop];
        });
        root.querySelectorAll('[data-for-types]').forEach(function (area) { area.hidden = area.dataset.forTypes.split(' ').indexOf(item.type) < 0; });
        root.querySelector('[data-selected-title]').textContent = labels[item.type];
        var index = state.blocks.indexOf(item);
        root.querySelector('[data-block-position]').textContent = (index + 1) + ' of ' + state.blocks.length;
        root.querySelector('[data-builder-action="up"]').disabled = index === 0;
        root.querySelector('[data-builder-action="down"]').disabled = index === state.blocks.length - 1;
        loadingEditors = false;
        highlight();
    }
    function select(blockId) { if (selected === blockId) return; flush(); selected = blockId; inspector(); }
    function escapeSelection(event) {
        if (event.key !== 'Escape' || event.defaultPrevented || previewMode || activeDrag || !selected) return;
        event.preventDefault();
        select(null);
    }
    function settings() {
        ['background', 'color', 'font', 'background_image'].forEach(function (key) { document.getElementById('builder-' + key.replace(/_/g, '-')).value = state.settings[key]; });
        document.getElementById('lesson-preheader').value = state.settings.preheader;
    }
    function highlight() {
        if (!frame.contentDocument) return;
        frame.contentDocument.querySelectorAll('[data-email-block]').forEach(function (node) { node.classList.toggle('builder-selected', !previewMode && node.dataset.emailBlock === selected); });
        frame.contentDocument.body.classList.toggle('builder-preview', previewMode);
    }
    function insert(item, at) {
        if (state.blocks.length >= 40) { say('Use up to 40 blocks in one lesson.'); return; }
        flush(); item = copy(item); item.id = id();
        if (at === undefined) at = current() ? state.blocks.indexOf(current()) + 1 : state.blocks.length;
        change(function () { state.blocks.splice(at, 0, item); }); selected = item.id; inspector();
    }
    function removeBlock(blockId) {
        flush();
        var index = state.blocks.findIndex(function (item) { return item.id === blockId; });
        if (index < 0) return;
        change(function () { state.blocks.splice(index, 1); });
        if (selected === blockId || !current()) selected = (state.blocks[Math.min(index, state.blocks.length - 1)] || {}).id || null;
        inspector();
        say('Block deleted. Use Undo to restore it.');
    }
    async function request(url, options) {
        var response = await fetch(url, Object.assign({credentials: 'same-origin', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json'}}, options || {}));
        var data = response.status === 204 ? {} : await response.json().catch(function () { return {}; });
        if (!response.ok) {
            var message = data.errors ? Object.values(data.errors).flat()[0] : (response.status === 419 || response.status === 401 ? 'Your session expired. Keep this page open, sign in in another tab, then try again.' : 'The request failed. Try again.');
            throw new Error(message);
        }
        return data;
    }
    function schedulePreview() {
        previewVersion++;
        if (previewRequest) previewRequest.abort();
        clearTimeout(previewTimer);
        previewStatus.textContent = 'Preview updating…';
        previewTimer = setTimeout(renderPreview, 450);
    }
    async function renderPreview() {
        if (activeDrag) { previewTimer = setTimeout(renderPreview, 250); return; }
        var version = previewVersion;
        root.querySelector('[data-empty-canvas]').hidden = !!state.blocks.length;
        root.querySelector('[data-canvas-frame]').hidden = !state.blocks.length;
        if (!state.blocks.length) { previewStatus.textContent = 'No blocks'; return; }
        previewRequest = new AbortController();
        try {
            var result = await request(root.dataset.previewUrl, {method: 'POST', signal: previewRequest.signal, body: JSON.stringify({email_design: JSON.stringify(state), subject: form.querySelector('[name="subject"]').value})});
            if (version !== previewVersion) return;
            frame.style.height = '500px';
            frame.srcdoc = result.html;
            previewStatus.textContent = 'Preview updated';
        } catch (error) {
            if (error.name !== 'AbortError' && version === previewVersion) { previewStatus.textContent = 'Preview needs attention'; say(error.message); }
        }
    }
    function resizeFrame() {
        if (!frame.contentDocument || !frame.contentDocument.body) return;
        var width = device === 'phone' ? 375 : 680;
        var scroller = root.querySelector('.email-builder__canvas-scroll');
        var padding = global.getComputedStyle(scroller);
        var available = scroller.clientWidth - parseFloat(padding.paddingLeft) - parseFloat(padding.paddingRight);
        var scale = Math.min(1, Math.max(100, available) / width);
        frame.style.width = width + 'px';
        var height = Math.max(500, frame.contentDocument.documentElement.scrollHeight, frame.contentDocument.body.scrollHeight);
        frame.style.height = height + 'px';
        frame.style.transform = 'scale(' + scale + ')';
        var shell = root.querySelector('[data-canvas-frame]');
        shell.style.width = width * scale + 'px'; shell.style.height = height * scale + 'px';
    }
    function transfer(event, data) {
        activeDrag = data;
        event.dataTransfer.setData('application/x-pianolit-email', JSON.stringify(data));
        event.dataTransfer.effectAllowed = data.id ? 'move' : 'copy';
    }
    function drop(event, at) {
        if (previewMode) return;
        event.preventDefault(); event.stopPropagation();
        var data;
        try { data = JSON.parse(event.dataTransfer.getData('application/x-pianolit-email')); } catch (error) { return; }
        flush();
        if (data.id) { change(function () { state.blocks = move(state.blocks, data.id, at); }); selected = data.id; inspector(); }
        else if (labels[data.type]) insert(block(data.type, state.settings), at);
        else if (data.saved) { var entry = library.find(function (item) { return item.id === data.saved; }); if (entry) insert(entry.block, at); }
        activeDrag = null;
    }
    frame.addEventListener('load', function () {
        var doc = frame.contentDocument;
        if (!doc || !doc.body) return;
        var css = doc.createElement('style');
        css.textContent = '[data-email-block]{position:relative;cursor:pointer;transition:outline-color .1s}[data-email-block]:hover{outline:1px dashed #9ab8ff;outline-offset:-1px}.builder-selected{outline:2px solid #0055fe!important;outline-offset:-2px}.builder-control{position:absolute;top:0;border:0;padding:5px 9px;color:#fff;font:12px/14px Arial;z-index:2;opacity:0;cursor:pointer}.builder-drag{left:0;border-radius:0 0 5px 0;background:#0055fe;cursor:grab}.builder-delete{right:0;border-radius:0 0 0 5px;background:#b42318}[data-email-block]:hover .builder-control,.builder-selected .builder-control,.builder-control:focus{opacity:1}.builder-control:focus-visible{outline:2px solid #242933;outline-offset:2px}.builder-preview [data-email-block]{cursor:default;outline:none!important}.builder-preview .builder-control{display:none}.builder-drop{outline:3px dashed #0055fe!important;outline-offset:-3px}@media(hover:none){.builder-control{opacity:1}}';
        doc.head.appendChild(css);
        doc.addEventListener('click', function (event) {
            event.preventDefault();
            var target = event.target.closest('[data-email-block]');
            if (!previewMode && !activeDrag) select(target ? target.dataset.emailBlock : null);
        });
        doc.addEventListener('keydown', escapeSelection);
        doc.querySelectorAll('[data-email-block]').forEach(function (node) {
            var handle = doc.createElement('button'); handle.type = 'button'; handle.className = 'builder-control builder-drag'; handle.textContent = '⠿ Move'; handle.draggable = true;
            handle.setAttribute('aria-label', 'Select and drag ' + (labels[(state.blocks.find(function (b) { return b.id === node.dataset.emailBlock; }) || {}).type] || 'block'));
            handle.addEventListener('dragstart', function (event) { select(node.dataset.emailBlock); transfer(event, {id: node.dataset.emailBlock}); });
            handle.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); select(node.dataset.emailBlock); } });
            var remove = doc.createElement('button'); remove.type = 'button'; remove.className = 'builder-control builder-delete'; remove.textContent = 'Delete';
            var item = state.blocks.find(function (block) { return block.id === node.dataset.emailBlock; });
            remove.setAttribute('aria-label', 'Delete ' + (item && (item.title || labels[item.type]) || 'block'));
            remove.addEventListener('click', function (event) {
                event.preventDefault(); event.stopPropagation();
                if (!previewMode) removeBlock(node.dataset.emailBlock);
            });
            node.append(handle, remove);
            node.addEventListener('dragover', function (event) { if (!previewMode && activeDrag) { event.preventDefault(); node.classList.add('builder-drop'); } });
            node.addEventListener('dragleave', function () { node.classList.remove('builder-drop'); });
            node.addEventListener('drop', function (event) {
                node.classList.remove('builder-drop');
                var at = state.blocks.findIndex(function (item) { return item.id === node.dataset.emailBlock; });
                if (event.clientY > node.getBoundingClientRect().top + node.getBoundingClientRect().height / 2) at++;
                drop(event, at);
            });
        });
        doc.body.addEventListener('dragover', function (event) { if (!previewMode && activeDrag) event.preventDefault(); });
        doc.body.addEventListener('drop', function (event) { drop(event, state.blocks.length); });
        doc.addEventListener('dragend', function () { activeDrag = null; doc.querySelectorAll('.builder-drop').forEach(function (node) { node.classList.remove('builder-drop'); }); });
        doc.querySelectorAll('img').forEach(function (image) { image.addEventListener('load', resizeFrame); image.addEventListener('error', resizeFrame); });
        highlight(); resizeFrame();
    });
    root.querySelectorAll('[data-add-block]').forEach(function (button) {
        button.addEventListener('click', function () { insert(block(button.dataset.addBlock, state.settings)); });
        button.addEventListener('dragstart', function (event) { transfer(event, {type: button.dataset.addBlock}); });
    });
    root.addEventListener('dragend', function () { activeDrag = null; });
    root.addEventListener('keydown', escapeSelection);
    root.querySelector('.email-builder__stage').addEventListener('click', function (event) {
        if (previewMode || activeDrag || event.target.closest('button, a, input, select, textarea, label, [role="group"]')) return;
        select(null);
    });
    var empty = root.querySelector('[data-empty-canvas]');
    empty.addEventListener('dragover', function (event) { if (activeDrag) event.preventDefault(); });
    empty.addEventListener('drop', function (event) { drop(event, state.blocks.length); });
    props.forEach(function (prop) {
        element(prop).addEventListener('input', function () {
            if (!current() || loadingEditors) return;
            var value = numeric.indexOf(prop) >= 0 ? Number(this.value) : this.value;
            change(function () { current()[prop] = value; }, selected + prop);
        });
    });
    ['background', 'color', 'font', 'background_image'].forEach(function (key) {
        document.getElementById('builder-' + key.replace(/_/g, '-')).addEventListener('input', function () {
            var value = this.value;
            change(function () {
                var previous = state.settings[key]; state.settings[key] = value;
                if (key === 'background' || key === 'color') state.blocks.forEach(function (item) { if (item[key] === previous) item[key] = value; });
            }, 'settings-' + key);
            inspector();
        });
    });
    document.getElementById('lesson-preheader').addEventListener('input', function () { var value = this.value; change(function () { state.settings.preheader = value; }, 'preheader'); });
    form.querySelector('[name="subject"]').addEventListener('input', function () { sync(); schedulePreview(); say('Unsaved changes'); });
    root.querySelectorAll('[data-device]').forEach(function (button) {
        button.addEventListener('click', function () {
            root.querySelectorAll('[data-device]').forEach(function (item) { item.setAttribute('aria-pressed', String(item === button)); });
            device = button.dataset.device;
            frame.style.width = device === 'phone' ? '375px' : '680px';
            frame.style.height = '500px';
            setTimeout(resizeFrame, 50);
        });
    });
    function libraryView() {
        var container = root.querySelector('[data-block-library]'); container.replaceChildren();
        if (!library.length) { var text = document.createElement('p'); text.className = 'small text-muted'; text.textContent = 'No saved blocks yet.'; container.appendChild(text); }
        library.forEach(function (entry) {
            var row = document.createElement('div'); row.className = 'email-builder__saved-block';
            var add = document.createElement('button'); add.type = 'button'; add.className = 'btn btn-sm btn-secondary'; add.textContent = entry.name; add.draggable = true;
            add.addEventListener('click', function () { insert(entry.block); }); add.addEventListener('dragstart', function (event) { transfer(event, {saved: entry.id}); });
            var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-secondary'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Delete saved block ' + entry.name);
            remove.addEventListener('click', async function () {
                if (!global.confirm('Delete “' + entry.name + '” from the shared library? Existing emails will keep their copies.')) return;
                remove.disabled = true;
                try { await request(root.dataset.libraryUrl + '/' + entry.id, {method: 'DELETE'}); library = library.filter(function (item) { return item.id !== entry.id; }); libraryView(); say('Saved block deleted.'); }
                catch (error) { say(error.message); remove.disabled = false; }
            });
            row.append(add, remove); container.appendChild(row);
        });
    }
    async function loadLibrary() {
        try { var data = await request(root.dataset.libraryUrl); library = data.blocks; libraryView(); }
        catch (error) {
            var container = root.querySelector('[data-block-library]'); container.textContent = 'Saved blocks could not be loaded. ';
            var retry = document.createElement('button'); retry.type = 'button'; retry.className = 'btn btn-sm btn-secondary'; retry.textContent = 'Retry'; retry.addEventListener('click', loadLibrary); container.appendChild(retry);
        }
    }
    root.querySelectorAll('[data-upload-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (uploading) return;
            flush();
            var key = button.dataset.uploadTarget, item = key === 'settings' ? state.settings : current();
            if (!item) return;
            uploadTarget = {id: key === 'settings' ? null : item.id, key: key === 'settings' ? 'background_image' : key, previous: item[key === 'settings' ? 'background_image' : key]};
            root.querySelector('[data-upload-panel]').hidden = false;
            root.querySelector('[data-upload-panel]').scrollIntoView({block: 'nearest', behavior: 'auto'});
            document.getElementById('email-builder-upload').focus();
        });
    });
    root.addEventListener('click', async function (event) {
        var button = event.target.closest('[data-builder-action]');
        if (!button) return;
        var action = button.dataset.builderAction; flush();
        var item = current(), index = item ? state.blocks.indexOf(item) : -1;
        if (action === 'up' && index > 0) { change(function () { state.blocks = move(state.blocks, selected, index - 1); }); inspector(); }
        if (action === 'down' && index < state.blocks.length - 1 && index >= 0) { change(function () { state.blocks = move(state.blocks, selected, index + 2); }); inspector(); }
        if (action === 'duplicate' && item) insert(item);
        if (action === 'remove' && item) removeBlock(item.id);
        if (action === 'undo' || action === 'redo') {
            var source = action === 'undo' ? history : future, target = action === 'undo' ? future : history;
            if (source.length) { target.push(JSON.stringify(state)); state = JSON.parse(source.pop()); lastEdit = ''; if (!current()) selected = null; sync(); settings(); inspector(); schedulePreview(); say(dirty ? 'Unsaved changes' : 'No unsaved changes'); }
        }
        if (action === 'starter') {
            if (state.blocks.length > 36) { say('Use up to 40 blocks in one lesson.'); return; }
            change(function () {
                var hero = block('hero', state.settings); hero.content = '<p>A short introduction to this lesson.</p>'; hero.label = '';
                var text = block('text', state.settings); text.content = '<p>Hello [first_name],</p><p>Write the main lesson here.</p>';
                var panel = block('panel', state.settings); panel.content = '<p>Add a practical exercise or a question for the reader.</p>'; panel.label = '';
                state.blocks.push(hero, text, panel, block('button', state.settings)); selected = hero.id;
            }); inspector();
        }
        if (action === 'preview') { previewMode = !previewMode; root.classList.toggle('email-builder--preview', previewMode); button.textContent = previewMode ? 'Back to editing' : 'Preview'; highlight(); resizeFrame(); }
        if (action === 'save-block' && item) {
            var name = element('save_name').value.trim();
            if (!name) { element('save_name').focus(); say('Enter a name for the saved block.'); return; }
            button.disabled = true;
            try { var saved = await request(root.dataset.libraryUrl, {method: 'POST', body: JSON.stringify({name: name, block: copy(item)})}); library.push(saved); library.sort(function (a, b) { return a.name.localeCompare(b.name); }); libraryView(); say('Block saved to the shared library.'); }
            catch (error) { say(error.message); }
            finally { button.disabled = false; }
        }
        if (action === 'cancel-upload') { uploadVersion++; uploadTarget = null; root.querySelector('[data-upload-panel]').hidden = true; }
        if (action === 'upload') {
            var input = document.getElementById('email-builder-upload');
            if (!input.files.length || !input.reportValidity() || !uploadTarget || uploading) return;
            var target = copy(uploadTarget), version = ++uploadVersion, data = new FormData(); data.append('file', input.files[0]);
            uploading = true; button.disabled = true; sync(); say('Uploading image…');
            try {
                var result = await request(root.dataset.uploadUrl, {method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: data});
                var owner = target.id ? state.blocks.find(function (b) { return b.id === target.id; }) : state.settings;
                if (version === uploadVersion && owner && owner[target.key] === target.previous) {
                    change(function () { owner[target.key] = result.location; }); settings(); inspector(); root.querySelector('[data-upload-panel]').hidden = true;
                    root.querySelector('[data-file-clear]').click(); say('Image uploaded. Save the lesson to keep this change.');
                } else say('The upload finished after its target changed. Select the image again if you still want to use it.');
            } catch (error) { if (version === uploadVersion) say(error.message); }
            finally { uploading = false; button.disabled = false; sync(); }
        }
    });
    function initializeEditors() {
        if (!global.tinymce) { say('The rich-text editor could not load. Text remains available as HTML; reload to restore formatting controls.'); return; }
        ['content', 'content_right'].forEach(function (prop) {
            global.tinymce.init({target: element(prop), height: 300, menubar: false, branding: false, relative_urls: false, convert_urls: false,
                plugins: 'lists link', toolbar: 'undo redo | formatselect | bold italic | link | bullist numlist | alignleft aligncenter alignright | forecolor',
                content_style: 'body{font:16px/1.65 Arial,sans-serif;padding:8px;color:#242933}img{max-width:100%;height:auto}',
                setup: function (editor) {
                    editor.on('init', function () { editorReady[prop] = true; loadingEditors = true; setEditor(prop, current() ? current()[prop] : ''); loadingEditors = false; });
                    editor.on('input change undo redo', function () { if (!loadingEditors && editorReady[prop] && current()) { var value = editor.getContent(); change(function () { current()[prop] = value; }, selected + prop); } });
                }
            });
        });
    }
    form.addEventListener('submit', function (event) {
        flush(); sync();
        if (uploading || !state.blocks.length) { event.preventDefault(); say(uploading ? 'Wait for the image upload before saving.' : 'Add at least one block before saving the lesson.'); return; }
        submitting = !event.defaultPrevented;
    });
    global.addEventListener('beforeunload', function (event) { if ((dirty || uploading) && !submitting) { event.preventDefault(); event.returnValue = ''; } });
    global.addEventListener('pageshow', function () { submitting = false; sync(); });
    global.addEventListener('resize', function () { frame.style.height = '500px'; resizeFrame(); });
    settings(); sync(); inspector(); initializeEditors(); loadLibrary(); schedulePreview();
})(typeof window === 'undefined' ? globalThis : window);
