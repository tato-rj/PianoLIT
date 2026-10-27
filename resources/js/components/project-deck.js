(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else {
        const start = () => root.document.querySelectorAll('[data-project-deck]').forEach(deck => factory(deck, root));
        if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', start);
        else start();
    }
})(typeof window !== 'undefined' ? window : this, function initProjectDeck(deck, win) {
    if (deck.dataset.projectReady) return;
    const cards = Array.from(deck.querySelectorAll('[data-project]'));
    if (cards.length < 2) return;
    deck.dataset.projectReady = 'true';
    const mobile = win.matchMedia('(max-width: 767px)');
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)');
    const doc = win.document;
    const properties = ['--project-compact-height', '--project-expansion', '--project-copy-height', '--project-photo-height', '--project-scroll-tail'];
    let metrics = null, frame = null, away = false;
    const scrollTop = () => win.scrollY || 0;
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    function cancel() {
        if (frame !== null) win.cancelAnimationFrame(frame);
        frame = null;
    }
    function reset() {
        cancel();
        metrics = null;
        delete deck.dataset.mobileCards;
        delete deck.dataset.projectActive;
        properties.forEach(name => deck.style.removeProperty(name));
        cards.forEach(card => card.style.removeProperty('--project-open'));
    }
    function render() {
        frame = null;
        if (!metrics || away || doc.hidden) return;
        const progress = clamp((scrollTop() - metrics.start) / metrics.step, 0, cards.length - 1);
        cards.forEach((card, index) => {
            const openness = Math.max(0, 1 - Math.abs(progress - index));
            card.style.setProperty('--project-open', openness.toFixed(5));
        });
        deck.dataset.projectActive = cards[Math.round(progress)].dataset.project;
    }
    function schedule() {
        if (metrics && !away && !doc.hidden && frame === null) frame = win.requestAnimationFrame(render);
    }
    function measure() {
        cancel();
        if (!mobile.matches || reduced.matches) { reset(); return; }
        deck.dataset.mobileCards = 'true';
        deck.style.setProperty('--project-scroll-tail', '0px');
        // Measure both copy sizes before setting the shared image/expansion size.
        // No clones: the same seven native links remain in normal document flow.
        cards.forEach(card => card.style.setProperty('--project-open', '1'));
        const width = cards[0].getBoundingClientRect().width;
        const copyHeight = Math.ceil(Math.max(...cards.map(card => card.querySelector('.project-card__copy').getBoundingClientRect().height)));
        cards.forEach(card => card.style.setProperty('--project-open', '0'));
        const compactHeight = Math.max(144, Math.ceil(Math.max(...cards.map(card => card.querySelector('.project-card__copy').getBoundingClientRect().height))));
        const viewportHeight = win.innerHeight;
        const photoHeight = Math.min(width, Math.max(160, viewportHeight - copyHeight - 64));
        const fullHeight = Math.max(compactHeight, copyHeight + photoHeight);
        const anchor = Math.max(16, (viewportHeight - fullHeight) / 2);
        const gap = parseFloat(win.getComputedStyle(deck.querySelector('.row')).rowGap) || 0;
        const deckTop = deck.getBoundingClientRect().top + scrollTop();
        metrics = {width: win.innerWidth, start: deckTop - anchor, step: compactHeight + gap};
        deck.style.setProperty('--project-compact-height', compactHeight + 'px');
        deck.style.setProperty('--project-expansion', (fullHeight - compactHeight) + 'px');
        deck.style.setProperty('--project-copy-height', copyHeight + 'px');
        deck.style.setProperty('--project-photo-height', photoHeight + 'px');
        render();
        // Leave enough native scroll room to fully open the final card, even on
        // tall phones. Include the existing footer in that available space.
        const belowDeck = Math.max(0, doc.documentElement.scrollHeight - (deck.getBoundingClientRect().bottom + scrollTop()));
        const tail = Math.max(0, viewportHeight - anchor - fullHeight - belowDeck);
        deck.style.setProperty('--project-scroll-tail', Math.ceil(tail) + 'px');
    }

    win.addEventListener('scroll', schedule, {passive: true});
    win.addEventListener('resize', () => {
        // Mobile browser chrome changes height while scrolling. Keep the measured
        // anchors stable until the layout width/orientation actually changes.
        if (!metrics || metrics.width !== win.innerWidth) measure();
        else schedule();
    });
    win.addEventListener('load', measure);
    win.addEventListener('pagehide', () => { away = true; cancel(); });
    win.addEventListener('pageshow', () => { away = false; measure(); });
    doc.addEventListener('visibilitychange', () => doc.hidden ? cancel() : schedule());
    deck.addEventListener('focusin', event => {
        if (!metrics) return;
        const card = event.target.closest('[data-project]');
        const index = cards.indexOf(card);
        if (index < 0 || !card.matches(':focus-visible')) return;
        win.scrollTo({top: Math.max(0, metrics.start + index * metrics.step), behavior: 'instant'});
        schedule();
    });
    [mobile, reduced].forEach(media => {
        if (media.addEventListener) media.addEventListener('change', measure);
        else if (media.addListener) media.addListener(measure);
    });
    if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(measure);
    measure();
});
