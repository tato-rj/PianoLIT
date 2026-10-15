(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.AdminTableLoading = factory(root);
}(typeof window !== 'undefined' ? window : this, function (win) {
    'use strict';
    var states = new WeakMap();
    var bound = false;

    function hasRows(table) {
        return table.tBodies.length && Array.prototype.some.call(table.tBodies[0].rows, function (row) {
            return row.cells.length > 1;
        });
    }

    function decorate(table) {
        Array.prototype.forEach.call(table.tBodies[0].rows, function (row) {
            Array.prototype.forEach.call(row.cells, function (cell, index) {
                var header = table.tHead.rows[0].cells[index];
                var heading = header.textContent.trim().toLowerCase();
                // DataTables applies column classes to headers too. Match them
                // before initialization so the skeleton uses the same widths.
                ['dataTables_main_column', 'text-nowrap', 'text-end'].forEach(function (name) {
                    if (cell.classList.contains(name)) header.classList.add(name);
                });
                var buttons = cell.querySelectorAll('.btn, button');
                var shape = cell.querySelector('.status-toggle') ? 'toggle' :
                    cell.querySelector('input[type="checkbox"]') ? 'checkbox' :
                    buttons.length > 1 ? 'actions' : buttons.length ? 'button' :
                    heading === 'id' ? 'id' : 'text';
                cell.setAttribute('data-table-placeholder', shape);
            });
        });
    }

    function release(state) {
        ++state.generation;
        win.clearTimeout(state.timer);
        state.frame.classList.remove('is-loading', 'is-starting', 'is-initializing');
        state.frame.style.minHeight = '';
        state.table.setAttribute('aria-busy', 'false');
        state.table.tBodies[0].removeAttribute('inert');
        state.table.tBodies[0].removeAttribute('aria-hidden');
        state.status.hidden = true;
    }

    function start(state) {
        ++state.generation;
        win.clearTimeout(state.timer);
        if (!hasRows(state.table)) { release(state); return; }
        decorate(state.table);
        state.frame.style.minHeight = state.frame.getBoundingClientRect().height + 'px';
        state.frame.classList.add('is-loading');
        state.table.setAttribute('aria-busy', 'true');
        state.table.tBodies[0].setAttribute('inert', '');
        state.table.tBodies[0].setAttribute('aria-hidden', 'true');
        state.status.hidden = false;
        // Leave the server-rendered table usable if initialization never completes.
        state.timer = win.setTimeout(function () { release(state); }, 20000);
    }

    function finish(state) {
        if (!hasRows(state.table)) { release(state); return; }
        decorate(state.table);
        var generation = state.generation;
        function paint() {
            win.requestAnimationFrame(function () {
                win.requestAnimationFrame(function () {
                    if (generation === state.generation) release(state);
                });
            });
        }
        // Fonts can alter column widths. Reveal after layout, without a minimum delay.
        var fonts = win.document.fonts;
        if (state.frame.classList.contains('is-starting') && fonts && fonts.ready) fonts.ready.then(paint, paint);
        else paint();
    }

    function prepare(table) {
        if (!table || states.has(table)) return;
        var frame = table.closest('[data-admin-datatable]');
        if (!frame) return;
        var state = {table: table, frame: frame, status: frame.querySelector('[data-admin-table-status]'), generation: 0};
        states.set(table, state);
        if (!hasRows(table)) return;
        frame.classList.add('is-initializing', 'is-starting');
        start(state);
    }

    function bind($) {
        if (!$ || bound) return;
        bound = true;
        $(win.document).on('preInit.dt.adminTableLoading', 'table', function () {
            var state = states.get(this);
            if (state) state.frame.classList.remove('is-initializing');
        }).on('preXhr.dt.adminTableLoading', 'table', function () {
            var state = states.get(this);
            if (state) start(state);
        }).on('draw.dt.adminTableLoading', 'table', function () {
            var state = states.get(this);
            if (state) finish(state);
        }).on('xhr.dt.adminTableLoading', 'table', function (event, settings, json, xhr) {
            var state = states.get(this);
            if (!state || xhr !== settings.jqXHR || (json && Number(json.draw) < settings.iDraw)) return;
            if (!json || json.error) release(state);
        }).on('destroy.dt.adminTableLoading', 'table', function () {
            var state = states.get(this);
            if (state) { release(state); states.delete(this); }
        });
    }

    return {prepare: prepare, bind: bind};
}));
