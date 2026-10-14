(function () {
    'use strict';

    function install(root) {
        if (root.dataset.mapReady) return;
        root.dataset.mapReady = 'true';
        const svg = root.querySelector('.admin-country-map');
        const stage = root.querySelector('.admin-country-stage');
        const tooltip = root.querySelector('.admin-map-tooltip');
        const picker = root.querySelector('[data-country-picker]');
        const located = Number(root.dataset.located) || 0;
        const unit = root.dataset.mapUnit || 'user';
        const units = root.dataset.mapUnits || 'users';
        const shareLabel = root.dataset.mapShareLabel || 'of accounts with a country';
        const width = Number(svg.dataset.mapWidth), height = Number(svg.dataset.mapHeight);
        const shapes = new Map(), countries = new Map();
        const number = new Intl.NumberFormat('en-US');
        let zoom = 1, x = 0, y = 0, drag = null, suppressClickUntil = 0;
        const clamp = (value, min, max) => Math.max(min, Math.min(value, max));

        root.querySelectorAll('.admin-country-shape').forEach(shape => {
            const code = shape.dataset.country;
            shapes.set(code, shape);
            countries.set(code, {name: shape.dataset.name, count: Number(shape.dataset.count) || 0});
            if (Number(shape.dataset.count) > 0) {
                shape.setAttribute('role', 'button');
                shape.setAttribute('tabindex', '0');
                shape.setAttribute('aria-pressed', shape.classList.contains('is-selected') ? 'true' : 'false');
            }
        });
        Array.from(picker.options).forEach(option => countries.set(option.value, {name: option.dataset.name, count: Number(option.dataset.count) || 0}));

        function hideTooltip() { tooltip.hidden = true; }

        function showTooltip(shape, event) {
            const country = countries.get(shape.dataset.country);
            if (!country) return;
            tooltip.querySelector('strong').textContent = country.name;
            tooltip.querySelector('span').textContent = number.format(country.count) + ' ' + (country.count === 1 ? unit : units);
            tooltip.hidden = false;
            const bounds = stage.getBoundingClientRect();
            const countryBounds = shape.getBoundingClientRect();
            const matrix = svg.getScreenCTM();
            const center = svg.createSVGPoint();
            center.x = Number(shape.dataset.centerX); center.y = Number(shape.dataset.centerY);
            const projected = matrix ? center.matrixTransform(matrix) : null;
            const pointX = event && Number.isFinite(event.clientX) ? event.clientX : (projected ? projected.x : countryBounds.x + countryBounds.width / 2);
            const pointY = event && Number.isFinite(event.clientY) ? event.clientY : (projected ? projected.y : countryBounds.y + countryBounds.height / 2);
            tooltip.style.left = clamp(pointX - bounds.x + 12, 0, Math.max(0, bounds.width - tooltip.offsetWidth)) + 'px';
            tooltip.style.top = clamp(pointY - bounds.y - tooltip.offsetHeight - 10, 0, Math.max(0, bounds.height - tooltip.offsetHeight)) + 'px';
        }

        function select(code) {
            const country = countries.get(code);
            if (!country) return;
            shapes.forEach((shape, key) => {
                shape.classList.toggle('is-selected', key === code);
                if (shape.hasAttribute('tabindex')) shape.setAttribute('aria-pressed', key === code ? 'true' : 'false');
            });
            root.querySelectorAll('[data-select-country]').forEach(button => button.setAttribute('aria-pressed', button.dataset.selectCountry === code ? 'true' : 'false'));
            root.querySelector('[data-country-total]').textContent = number.format(country.count);
            root.querySelector('[data-country-name]').textContent = country.name;
            root.querySelector('[data-country-share]').textContent = (located ? Math.round(country.count / located * 1000) / 10 : 0) + '% ' + shareLabel;
            const flag = root.querySelector('[data-country-flag]');
            flag.className = /^[A-Z]{2}$/.test(code) ? 'flag-icon flag-icon-' + code.toLowerCase() : '';
            picker.value = code;
        }

        function renderView() {
            x = clamp(x, 0, width - width / zoom);
            y = clamp(y, 0, height - height / zoom);
            svg.setAttribute('viewBox', [x, y, width / zoom, height / zoom].join(' '));
            stage.classList.toggle('is-zoomed', zoom > 1);
            root.querySelector('[data-map-zoom="in"]').disabled = zoom >= 4;
            root.querySelector('[data-map-zoom="out"]').disabled = zoom <= 1;
            root.querySelector('[data-map-reset]').disabled = zoom <= 1;
            hideTooltip();
        }

        root.querySelectorAll('[data-map-zoom]').forEach(button => button.addEventListener('click', () => {
            const centerX = x + width / zoom / 2, centerY = y + height / zoom / 2;
            zoom = clamp(zoom * (button.dataset.mapZoom === 'in' ? 1.4 : 1 / 1.4), 1, 4);
            if (zoom < 1.01) zoom = 1;
            x = centerX - width / zoom / 2;
            y = centerY - height / zoom / 2;
            renderView();
        }));
        root.querySelector('[data-map-reset]').addEventListener('click', () => { zoom = 1; x = 0; y = 0; renderView(); });
        root.querySelectorAll('[data-select-country]').forEach(button => button.addEventListener('click', () => { select(button.dataset.selectCountry); hideTooltip(); }));
        picker.addEventListener('change', () => { select(picker.value); hideTooltip(); });

        function countryTarget(event) {
            return event.target.closest && event.target.closest('.admin-country-shape');
        }
        svg.addEventListener('pointermove', event => {
            if (drag && event.pointerId === drag.id) {
                if (Math.abs(event.clientX - drag.clientX) + Math.abs(event.clientY - drag.clientY) > 4) drag.moved = true;
                if (drag.moved) {
                    const bounds = svg.getBoundingClientRect();
                    x = drag.x - (event.clientX - drag.clientX) * width / zoom / bounds.width;
                    y = drag.y - (event.clientY - drag.clientY) * height / zoom / bounds.height;
                    renderView();
                }
                return;
            }
            const shape = countryTarget(event);
            if (shape) showTooltip(shape, event);
            else hideTooltip();
        });
        svg.addEventListener('pointerleave', hideTooltip);
        svg.addEventListener('click', event => {
            if (Date.now() < suppressClickUntil) return;
            const shape = countryTarget(event);
            if (shape) { select(shape.dataset.country); showTooltip(shape, event); }
        });
        svg.addEventListener('focusin', event => {
            const shape = countryTarget(event);
            if (shape) showTooltip(shape);
        });
        svg.addEventListener('focusout', hideTooltip);
        svg.addEventListener('keydown', event => {
            if (event.key === 'Escape') { hideTooltip(); return; }
            const shape = countryTarget(event);
            if (shape && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault(); select(shape.dataset.country); showTooltip(shape);
            }
        });
        svg.addEventListener('pointerdown', event => {
            if (zoom <= 1 || (event.pointerType === 'mouse' && event.button !== 0)) return;
            drag = {id: event.pointerId, clientX: event.clientX, clientY: event.clientY, x, y, moved: false, shape: countryTarget(event)};
            svg.setPointerCapture(event.pointerId);
            stage.classList.add('is-dragging');
        });
        function stopDrag(event, cancelled) {
            if (!drag || drag.id !== event.pointerId) return;
            if (drag.moved) suppressClickUntil = Date.now() + 250;
            else if (!cancelled && drag.shape) { select(drag.shape.dataset.country); showTooltip(drag.shape, event); suppressClickUntil = Date.now() + 250; }
            drag = null;
            stage.classList.remove('is-dragging');
            if (svg.hasPointerCapture(event.pointerId)) svg.releasePointerCapture(event.pointerId);
        }
        svg.addEventListener('pointerup', event => stopDrag(event, false));
        svg.addEventListener('pointercancel', event => stopDrag(event, true));
        svg.addEventListener('lostpointercapture', event => stopDrag(event, true));
        document.addEventListener('pointerdown', event => { if (!root.contains(event.target)) hideTooltip(); });
        window.addEventListener('resize', hideTooltip);
        root.classList.add('is-interactive');
        root.querySelector('.admin-country-controls').hidden = false;
        root.querySelector('.admin-country-picker').hidden = countries.size === 0 || picker.options.length === 0;
        renderView();
    }

    function ready() { document.querySelectorAll('[data-user-country-map], [data-country-map]').forEach(install); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
    else ready();
}());
