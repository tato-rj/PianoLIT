(function () {
    'use strict';

    var gallery = document.querySelector('[data-design-system]');
    if (!gallery) return;

    function previewFor(sample) {
        var preview = sample.querySelector('[data-ds-preview]');
        if (!preview) return null;
        if (preview.hasAttribute('data-ds-icon')) return preview.querySelector('svg');
        return preview.querySelector('.admin-table') || preview;
    }

    function measureSample(sample) {
        var preview = previewFor(sample);
        var output = sample.querySelector('[data-ds-specs]');
        if (!preview || !output) return;
        var style = window.getComputedStyle(preview);
        var properties = sample.getAttribute('data-ds-properties').split(',');
        var fragment = document.createDocumentFragment();
        properties.forEach(function (property) {
            var label = document.createElement('dt');
            var value = document.createElement('dd');
            label.textContent = property;
            value.textContent = style.getPropertyValue(property).trim() || '—';
            fragment.appendChild(label);
            fragment.appendChild(value);
        });
        output.textContent = '';
        output.appendChild(fragment);
    }

    function hexColor(value) {
        var match = value.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!match) return value;
        return '#' + match.slice(1).map(function (part) {
            return ('0' + Number(part).toString(16)).slice(-2);
        }).join('').toUpperCase();
    }

    function refresh() {
        gallery.querySelectorAll('[data-ds-sample]').forEach(measureSample);
        var rootStyle = window.getComputedStyle(document.documentElement);
        gallery.querySelectorAll('[data-ds-token]').forEach(function (element) {
            element.textContent = rootStyle.getPropertyValue(element.getAttribute('data-ds-token')).trim();
        });
        gallery.querySelectorAll('[data-ds-color]').forEach(function (element) {
            element.parentNode.querySelector('[data-ds-color-value]').textContent = hexColor(window.getComputedStyle(element).backgroundColor);
        });
        gallery.querySelectorAll('[data-ds-space]').forEach(function (element) {
            element.parentNode.querySelector('[data-ds-space-value]').textContent = window.getComputedStyle(element).width;
        });
    }

    var frame;
    function scheduleRefresh() {
        window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(refresh);
    }

    // Read states after the browser applies hover, focus, active and choice changes.
    ['pointerover', 'pointerout', 'pointerdown', 'pointerup', 'focusin', 'focusout', 'change', 'keyup'].forEach(function (event) {
        gallery.addEventListener(event, scheduleRefresh);
    });
    gallery.addEventListener('transitionend', scheduleRefresh);
    window.addEventListener('resize', scheduleRefresh);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(scheduleRefresh);
    refresh();
}());
