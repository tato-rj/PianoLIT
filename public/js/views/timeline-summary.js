(function (doc, win) {
    'use strict';
    const modal = doc.getElementById('timeline-summary-modal');
    if (!modal || !win.bootstrap?.Modal) return;

    const title = doc.getElementById('timeline-summary-title');
    const text = doc.getElementById('timeline-summary-text');
    modal.addEventListener('show.bs.modal', function (event) {
        const item = event.relatedTarget?.closest('.piece-timeline-event');
        if (!item) {
            event.preventDefault();
            return;
        }
        title.textContent = item.querySelector('.piece-timeline-title').textContent;
        text.textContent = item.querySelector('.piece-timeline-description').textContent;
    });

    doc.querySelectorAll('.piece-timeline').forEach(section => section.classList.add('has-summary-modal'));
    const descriptions = doc.querySelectorAll('.piece-timeline-description');
    function update(description) {
        description.nextElementSibling.hidden = description.scrollHeight <= description.clientHeight;
    }
    function updateAll() {
        descriptions.forEach(update);
    }

    if (win.ResizeObserver) {
        const observer = new win.ResizeObserver(entries => entries.forEach(({target}) => update(target)));
        descriptions.forEach(description => observer.observe(description));
    } else {
        win.addEventListener('resize', updateAll);
        doc.addEventListener('shown.bs.tab', updateAll);
    }
    doc.fonts?.ready.then(updateAll);
    updateAll();
}(document, window));
