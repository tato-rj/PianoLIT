(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else {
        const start = () => root.document.querySelectorAll('[data-project-deck]').forEach(deck => factory(deck, root));
        if (root.document.readyState === 'loading') root.document.addEventListener('DOMContentLoaded', start);
        else start();
    }
})(typeof window !== 'undefined' ? window : this, function initProjectDeck(deck, win) {
    if (deck.dataset.projectReady) return;
    deck.dataset.projectReady = 'true';
    const viewport = deck.querySelector('[data-project-viewport]');
    const cards = Array.from(viewport.querySelectorAll('[data-project]'));
    const doc = win.document;
    const reduced = win.matchMedia('(prefers-reduced-motion: reduce)');
    const speed = 22; // Pixels per second, independent of display refresh rate.
    const resumeDelay = 2400;
    const heldPointers = new Set();
    let pointer = null, suppressClick = false;
    let hovered = win.matchMedia('(hover: hover)').matches && viewport.matches(':hover');
    let touching = false, focused = false, visible = true, away = false;
    let frame = null, timer = null, lastTime = null, position = 0;
    const maximum = () => Math.max(0, viewport.scrollWidth - viewport.clientWidth);
    const clamp = left => Math.max(0, Math.min(maximum(), left));
    const canDrift = () => cards.length > 1 && !reduced.matches && !hovered && !touching && !focused &&
        !heldPointers.size && visible && !away && !doc.hidden && maximum() > 1 && viewport.scrollLeft < maximum();
    function pause() {
        if (frame !== null) win.cancelAnimationFrame(frame);
        if (timer !== null) win.clearTimeout(timer);
        frame = timer = lastTime = null;
    }
    function resume(delay = resumeDelay) {
        pause();
        if (!canDrift()) return;
        timer = win.setTimeout(() => {
            timer = null;
            if (!canDrift()) return;
            position = clamp(viewport.scrollLeft);
            frame = win.requestAnimationFrame(drift);
        }, delay);
    }
    function drift(time) {
        if (!canDrift()) { pause(); return; }
        // Keep subpixel progress even in browsers that round scrollLeft.
        const elapsed = lastTime === null ? 0 : Math.min(time - lastTime, 64);
        lastTime = time;
        position = clamp(position + speed * elapsed / 1000);
        viewport.scrollLeft = position;
        if (position >= maximum()) { pause(); return; }
        frame = win.requestAnimationFrame(drift);
    }
    deck.addEventListener('click', event => {
        // A drag must not follow a link when the mouse is released. Ordinary,
        // keyboard and modified clicks keep the original anchor behavior.
        if (suppressClick && event.detail !== 0) {
            suppressClick = false;
            event.preventDefault();
            event.stopPropagation();
            return;
        }
    });
    deck.addEventListener('keydown', event => {
        const card = event.target.closest('[data-project]');
        if (!card) return;
        focused = true;
        pause();
        const index = cards.indexOf(card);
        if (index < 0) return;
        const positions = {ArrowLeft: index - 1, ArrowRight: index + 1, Home: 0, End: cards.length - 1};
        if (!(event.key in positions)) return;
        event.preventDefault();
        cards[Math.max(0, Math.min(cards.length - 1, positions[event.key]))].focus();
        if (event.key === 'Home') viewport.scrollLeft = 0;
        if (event.key === 'End') viewport.scrollLeft = maximum();
    });
    viewport.addEventListener('wheel', event => {
        if (event.ctrlKey) return;
        const movement = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
        const delta = movement * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? viewport.clientWidth : 1);
        const next = clamp(viewport.scrollLeft + delta);
        // Both endpoints are real: let the page scroll once the row ends.
        if (Math.abs(next - viewport.scrollLeft) < 1) return;
        event.preventDefault();
        viewport.scrollLeft = next;
        resume();
    }, {passive: false});
    viewport.addEventListener('pointerdown', event => {
        suppressClick = false;
        heldPointers.add(event.pointerId);
        pause();
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        pointer = {id: event.pointerId, x: event.clientX, left: viewport.scrollLeft, dragged: false};
    });
    viewport.addEventListener('pointermove', event => {
        if (!pointer || pointer.id !== event.pointerId) return;
        const delta = event.clientX - pointer.x;
        if (!pointer.dragged && Math.abs(delta) < 8) return;
        if (!pointer.dragged) {
            pointer.dragged = true;
            viewport.setPointerCapture(event.pointerId);
            deck.classList.add('is-dragging');
        }
        event.preventDefault();
        viewport.scrollLeft = clamp(pointer.left - delta);
    });
    function endPointer(event) {
        heldPointers.delete(event.pointerId);
        if (pointer && pointer.id === event.pointerId) {
            suppressClick = pointer.dragged;
            pointer = null;
            deck.classList.remove('is-dragging');
            if (viewport.hasPointerCapture(event.pointerId)) viewport.releasePointerCapture(event.pointerId);
        }
        resume();
    }
    // Window listeners also catch a release outside the row before capture.
    win.addEventListener('pointerup', endPointer);
    win.addEventListener('pointercancel', endPointer);
    viewport.addEventListener('lostpointercapture', endPointer);
    viewport.addEventListener('pointerenter', event => {
        if (event.pointerType !== 'mouse') return;
        hovered = true;
        pause();
    });
    viewport.addEventListener('pointerleave', event => {
        if (event.pointerType !== 'mouse') return;
        hovered = false;
        resume();
    });
    viewport.addEventListener('touchstart', () => { touching = true; pause(); }, {passive: true});
    const endTouch = event => { touching = event.touches.length > 0; resume(); };
    win.addEventListener('touchend', endTouch, {passive: true});
    win.addEventListener('touchcancel', endTouch, {passive: true});
    deck.addEventListener('focusin', event => {
        focused = event.target.matches(':focus-visible');
        if (focused) pause();
    });
    deck.addEventListener('focusout', event => {
        if (deck.contains(event.relatedTarget)) return;
        focused = false;
        resume();
    });
    viewport.addEventListener('dragstart', event => event.preventDefault());
    // Native swiping/trackpad inertia gets a quiet period before drifting again.
    viewport.addEventListener('scroll', () => {
        // Preserve the animation's subpixel accumulator in browsers that round
        // scrollLeft; native swiping gets an idle period before resuming.
        if (frame === null) resume();
    }, {passive: true});
    deck.classList.add('is-enhanced');
    win.addEventListener('resize', () => {
        viewport.scrollLeft = clamp(viewport.scrollLeft);
        resume();
    });
    doc.addEventListener('visibilitychange', () => resume());
    win.addEventListener('pagehide', () => { away = true; pause(); });
    win.addEventListener('pageshow', () => { away = false; resume(); });
    if (reduced.addEventListener) reduced.addEventListener('change', () => resume());
    else if (reduced.addListener) reduced.addListener(() => resume());
    if (win.IntersectionObserver) {
        new win.IntersectionObserver(entries => {
            visible = entries[0].isIntersecting;
            resume(1200);
        }).observe(viewport);
    }
    resume(1200);
});
