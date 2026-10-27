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
    const properties = ['--project-compact-height', '--project-expansion', '--project-copy-height', '--project-photo-height', '--project-scroll-tail', '--project-tag-space', '--project-heading-collapse', '--project-action-travel'];
    const painted = new Map();
    let metrics = null, frame = null, away = false, progress = 0, lastTime = null;
    const scrollTop = () => win.scrollY || 0;
    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    function cancel() {
        if (frame !== null) win.cancelAnimationFrame(frame);
        frame = null;
        lastTime = null;
    }
    function reset() {
        cancel();
        metrics = null;
        delete deck.dataset.mobileCards;
        delete deck.dataset.projectActive;
        properties.forEach(name => deck.style.removeProperty(name));
        cards.forEach(card => {
            card.style.removeProperty('--project-open');
            card.parentElement.style.removeProperty('--project-shift');
        });
        painted.clear();
    }
    const targetProgress = () => clamp((scrollTop() - metrics.start) / metrics.step, 0, cards.length - 1);
    function paint() {
        let expansionBefore = 0;
        cards.forEach((card, index) => {
            const openness = Math.max(0, 1 - Math.abs(progress - index));
            const open = openness.toFixed(5);
            const shift = (expansionBefore * metrics.expansion).toFixed(3) + 'px';
            const previous = painted.get(card) || {};
            // Only the transitioning pair and the slot between them change.
            // Keep inactive cards out of style recalculation during scrolling.
            if (previous.open !== open) card.style.setProperty('--project-open', open);
            if (previous.shift !== shift) card.parentElement.style.setProperty('--project-shift', shift);
            painted.set(card, {open, shift});
            expansionBefore += openness;
        });
        const active = cards[Math.round(progress)].dataset.project;
        if (deck.dataset.projectActive !== active) deck.dataset.projectActive = active;
    }
    function render(time) {
        frame = null;
        if (!metrics || away || doc.hidden) return;
        const target = targetProgress();
        // Time-based easing follows both 60Hz and 120Hz displays, fills gaps
        // between scroll events, and stops requesting frames once settled.
        const elapsed = lastTime === null ? 1000 / 60 : clamp(time - lastTime, 0, 64);
        progress += (target - progress) * (1 - Math.exp(-elapsed / 45));
        if (Math.abs(target - progress) < .001) progress = target;
        paint();
        lastTime = time;
        if (progress !== target) schedule();
        else lastTime = null;
    }
    function schedule() {
        if (metrics && !away && !doc.hidden && frame === null) frame = win.requestAnimationFrame(render);
    }
    function measure() {
        cancel();
        if (!mobile.matches || reduced.matches) { reset(); return; }
        deck.dataset.mobileCards = 'true';
        deck.style.setProperty('--project-scroll-tail', '0px');
        // Measure once at full size; scroll frames only translate and clip this
        // fixed geometry. The same seven links remain in Bootstrap grid slots.
        cards.forEach(card => card.style.setProperty('--project-open', '1'));
        const width = cards[0].getBoundingClientRect().width;
        const copyHeight = Math.ceil(Math.max(...cards.map(card => card.querySelector('.project-card__copy').getBoundingClientRect().height)));
        const tag = cards[0].querySelector('.project-card__tag');
        const tagSpace = tag.getBoundingClientRect().height + (parseFloat(win.getComputedStyle(tag).marginBottom) || 0);
        const headingCollapse = cards[0].querySelector('h2').getBoundingClientRect().height * .25;
        const compactHeight = Math.max(144, Math.ceil(copyHeight - tagSpace - headingCollapse));
        const viewportHeight = win.innerHeight;
        const photoHeight = Math.min(width, Math.max(160, viewportHeight - copyHeight - 64));
        const fullHeight = Math.max(compactHeight, copyHeight + photoHeight);
        const anchor = Math.max(16, (viewportHeight - fullHeight) / 2);
        const gap = parseFloat(win.getComputedStyle(deck.querySelector('.row')).rowGap) || 0;
        const deckTop = deck.getBoundingClientRect().top + scrollTop();
        metrics = {width: win.innerWidth, start: deckTop - anchor, step: compactHeight + gap, expansion: fullHeight - compactHeight};
        deck.style.setProperty('--project-compact-height', compactHeight + 'px');
        deck.style.setProperty('--project-expansion', (fullHeight - compactHeight) + 'px');
        deck.style.setProperty('--project-copy-height', copyHeight + 'px');
        deck.style.setProperty('--project-photo-height', photoHeight + 'px');
        deck.style.setProperty('--project-tag-space', tagSpace + 'px');
        deck.style.setProperty('--project-heading-collapse', headingCollapse + 'px');
        deck.style.setProperty('--project-action-travel', (width - 74) + 'px');
        painted.clear();
        progress = targetProgress();
        paint();
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
    doc.addEventListener('visibilitychange', () => doc.hidden ? cancel() : measure());
    deck.addEventListener('focusin', event => {
        if (!metrics) return;
        const card = event.target.closest('[data-project]');
        const index = cards.indexOf(card);
        if (index < 0 || !card.matches(':focus-visible')) return;
        win.scrollTo({top: Math.max(0, metrics.start + index * metrics.step), behavior: 'instant'});
        cancel();
        progress = targetProgress();
        paint();
    });
    [mobile, reduced].forEach(media => {
        if (media.addEventListener) media.addEventListener('change', measure);
        else if (media.addListener) media.addListener(measure);
    });
    if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(measure);
    measure();
});
