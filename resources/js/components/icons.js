// Blade renders SVG immediately. This also covers AJAX fragments and class-based
// state changes while retaining the <i> wrapper and its existing event handlers.
const icons = require('../../icons/lucide.json');
const aliases = require('../../icons/aliases.json');
const selector = 'i.app-icon, i.fas, i.far, i.fa-solid';
const namespace = 'http://www.w3.org/2000/svg';

function resolve(name) {
    return aliases[name] || name;
}

function refreshIcon(element) {
    if (!element.matches || !element.matches(selector)) return;
    // Legacy HTML can still arrive from mobile-shared models/messages. Convert
    // its presentation here without changing those serialized API contracts.
    if (!element.classList.contains('app-icon')) {
        const legacy = Array.from(element.classList).find(token => token.indexOf('fa-') === 0 && icons[resolve(token.slice(3))]);
        if (!legacy) return;
        const name = resolve(legacy.slice(3));
        if (['heart', 'star', 'circle'].includes(name) && !element.classList.contains('far')) element.classList.add('icon-filled');
        element.classList.remove('fas', 'far', 'fa-solid', legacy);
        element.classList.add('app-icon', 'icon-' + name);
    }
    if (element.classList.contains('spinner-border')) {
        const current = element.querySelector('svg');
        if (current) current.remove();
        return;
    }
    const token = Array.from(element.classList).find(name => name.indexOf('icon-') === 0 && icons[resolve(name.slice(5))]);
    const name = token ? resolve(token.slice(5)) : 'circle-help';
    const current = element.querySelector('svg');
    if (current && current.getAttribute('data-lucide-name') === name) return;
    const svg = element.ownerDocument.createElementNS(namespace, 'svg');
    Object.entries({
        'data-lucide-name': name, width: '24', height: '24', viewBox: '0 0 24 24',
        fill: 'none', stroke: 'currentColor', 'stroke-width': '2',
        'stroke-linecap': 'round', 'stroke-linejoin': 'round',
        'aria-hidden': 'true', focusable: 'false'
    }).forEach(([key, value]) => svg.setAttribute(key, value));
    // Only locally generated, allowlisted Lucide geometry enters the SVG.
    svg.innerHTML = icons[name];
    if (current) current.remove();
    element.appendChild(svg);
    if (!element.hasAttribute('aria-label') && !element.hasAttribute('title')) element.setAttribute('aria-hidden', 'true');
}

function refresh(root) {
    refreshIcon(root);
    if (root.querySelectorAll) root.querySelectorAll(selector).forEach(refreshIcon);
}

function install(document) {
    refresh(document);
    const observer = new MutationObserver(records => {
        records.forEach(record => {
            if (record.type === 'attributes') refreshIcon(record.target);
            else record.addedNodes.forEach(refresh);
        });
    });
    observer.observe(document.documentElement, {subtree: true, childList: true, attributes: true, attributeFilter: ['class']});
    return observer;
}

module.exports = {resolve, refresh, refreshIcon, install};
if (typeof window !== 'undefined') {
    window.PianoIcons = module.exports;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => install(document));
    else install(document);
}
