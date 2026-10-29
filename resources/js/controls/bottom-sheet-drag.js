// Dragging is opt-in. Bootstrap still owns dismissal, focus, backdrop and cleanup.
export class BottomSheetDrag {
    constructor(panel, close) {
        this.panel = panel;
        this.close = close;
        this.handle = panel.querySelector('[data-sheet-drag-handle]');
        // Bootstrap's delegated dismiss listener runs in document capture, before
        // a handle can suppress the click produced by a drag. Own this button's
        // activation once initialized, while keeping its declarative fallback.
        this.handle.removeAttribute('data-bs-dismiss');
        this.gesture = null;
        this.suppressClick = false;
        this.handle.addEventListener('pointerdown', event => this.start(event));
        this.handle.addEventListener('pointermove', event => this.move(event));
        this.handle.addEventListener('pointerup', event => this.end(event));
        this.handle.addEventListener('pointercancel', event => this.cancel(event));
        this.handle.addEventListener('lostpointercapture', event => this.cancel(event));
        this.handle.addEventListener('click', event => {
            // A drag must not turn into a second dismissal or a tap through the sheet.
            // Native keyboard/assistive activation (detail 0) remains a normal button.
            if (this.suppressClick && event.detail !== 0) {
                event.preventDefault();
                event.stopPropagation();
            } else this.close();
        }, true);
        panel.addEventListener('hide.bs.offcanvas', () => {
            this.release();
            // Keep the current offset until Bootstrap adds .hiding, then its own
            // transform transition carries the sheet the rest of the way down.
            panel.classList.remove('is-sheet-dragging');
        });
        panel.addEventListener('hidden.bs.offcanvas', () => this.reset());
        panel.addEventListener('show.bs.offcanvas', () => this.reset());
        window.addEventListener('blur', () => this.cancel());
        window.addEventListener('resize', () => this.cancel());
        document.addEventListener('visibilitychange', () => { if (document.hidden) this.cancel(); });
    }

    start(event) {
        if (!event.isPrimary || event.button !== 0 || this.gesture
            || !this.panel.classList.contains('show')
            || this.panel.classList.contains('showing') || this.panel.classList.contains('hiding')) return;
        this.suppressClick = false;
        this.gesture = { id: event.pointerId, x: event.clientX, y: event.clientY,
            distance: 0, moved: false, height: this.panel.getBoundingClientRect().height,
            points: [{ distance: 0, time: event.timeStamp }] };
        this.handle.setPointerCapture(event.pointerId);
    }

    move(event) {
        const gesture = this.gesture;
        if (!gesture || event.pointerId !== gesture.id) return;
        const x = event.clientX - gesture.x, y = event.clientY - gesture.y;
        if (Math.hypot(x, y) >= 8) gesture.moved = true;
        if (!gesture.direction && gesture.moved) gesture.direction = Math.abs(y) > Math.abs(x) ? 'vertical' : 'horizontal';
        if (gesture.direction !== 'vertical') return;
        event.preventDefault();
        gesture.distance = Math.min(gesture.height, Math.max(0, y));
        gesture.points.push({ distance: gesture.distance, time: event.timeStamp });
        gesture.points = gesture.points.filter(point => event.timeStamp - point.time <= 100);
        this.panel.classList.add('is-sheet-dragging');
        this.panel.style.setProperty('--bottom-sheet-drag-y', `${gesture.distance}px`);
    }

    end(event) {
        const gesture = this.gesture;
        if (!gesture || event.pointerId !== gesture.id) return;
        this.move(event);
        this.suppressClick = gesture.moved;
        const first = gesture.points[0];
        const velocity = (gesture.distance - first.distance) / Math.max(1, event.timeStamp - first.time);
        const threshold = Math.min(120, Math.max(64, gesture.height * 0.22));
        const dismiss = gesture.direction === 'vertical'
            && (gesture.distance >= threshold || (gesture.distance >= 24 && velocity >= 0.55));
        this.release();
        this.panel.classList.remove('is-sheet-dragging');
        if (dismiss) this.close();
        else this.reset();
    }

    release() {
        const gesture = this.gesture;
        this.gesture = null;
        if (gesture && this.handle.hasPointerCapture(gesture.id)) this.handle.releasePointerCapture(gesture.id);
    }

    reset() {
        this.release();
        this.panel.classList.remove('is-sheet-dragging');
        this.panel.style.removeProperty('--bottom-sheet-drag-y');
    }

    cancel(event) {
        if (!this.gesture || (event && event.pointerId !== this.gesture.id)) return;
        this.suppressClick = true;
        this.reset();
    }
}
