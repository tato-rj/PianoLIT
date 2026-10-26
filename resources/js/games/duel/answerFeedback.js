const active = new WeakMap();

export function animateAnswerFeedback(label, correct) {
    if (!label) return;
    active.get(label)?.();
    const classes = ['animate__animated', 'animate__heartBeat', correct ? 'text-green' : 'text-red'];
    let timer;
    const cleanup = () => {
        clearTimeout(timer);
        label.removeEventListener('animationend', ended);
        label.removeEventListener('animationcancel', ended);
        label.classList.remove(...classes);
        active.delete(label);
    };
    const ended = event => { if (event.target === label) cleanup(); };
    // Restart even when consecutive answers have the same outcome.
    void label.offsetWidth;
    label.classList.add(...classes);
    label.addEventListener('animationend', ended);
    label.addEventListener('animationcancel', ended);
    const style = getComputedStyle(label);
    const milliseconds = value => (parseFloat(value) || 0) * (value.trim().endsWith('ms') ? 1 : 1000);
    const duration = Math.max(...style.animationDuration.split(',').map(milliseconds));
    const delay = Math.max(...style.animationDelay.split(',').map(milliseconds));
    timer = setTimeout(cleanup, duration + delay + 100);
    active.set(label, cleanup);
    return cleanup;
}
