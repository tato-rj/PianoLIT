const phases = {
    connecting: { label: 'Finding the beat', title: 'Connecting your duel…', description: 'We’re linking you with your opponent. Your game will open here.', icon: 'link' },
    ready: { label: 'Opponent connected', title: 'Ready to face the music?', description: 'Tap “I’m ready” below. When you’re both ready, a countdown starts the same game for both of you.', icon: 'bolt' },
    'waiting-ready': { label: 'You’re ready', title: 'One musician to go…', description: 'Your opponent still needs to tap “I’m ready”. Stay here — the countdown will start automatically.', icon: 'circle-check' },
    countdown: { label: 'Both players ready', title: 'Get ready to play!', description: 'Same challenge. Same starting line. Let’s see what you’ve got!', icon: 'music' },
    go: { label: 'The duel is on', title: 'Let’s play!', description: 'Make every note count.', icon: 'bolt' },
    'opponent-left': { label: 'Duel ended', title: 'Your opponent left', description: 'They’ve left this match, so the duel has ended. Head back to the games to play solo or challenge someone else.', icon: 'door-open' },
    'you-left': { label: 'Duel ended', title: 'You’ve left the duel', description: 'This match has ended. Choose another game whenever you’re ready.', icon: 'door-open' },
    expired: { label: 'Time’s up', title: 'This duel has expired', description: 'This room is no longer available. Head back to the games to start a fresh duel and share a new code.', icon: 'hourglass-end' },
    cancelled: { label: 'Duel ended', title: 'This duel was cancelled', description: 'This match is no longer active. You can start another duel or enjoy a solo game.', icon: 'flag-checkered' },
    idle: { label: 'Quick check-in', title: 'Still with us?', description: 'Keep the music going! Confirm before the timer reaches zero, or you’ll leave this duel and return to the games.', icon: 'hand' },
    lobby: { label: 'Your stage is set', title: 'Invite your opponent', description: 'Share this four-digit code. Your friend can choose “Join a Duel” and enter it. We’ll open the game as soon as they join.', icon: 'user-group' },
    joined: { label: 'Opponent connected', title: 'You’ve got a challenger!', description: 'Opening your game… Next, you’ll both choose when you’re ready.', icon: 'bolt' },
    join: { label: 'Challenge accepted', title: 'Join your friend’s duel', description: 'Enter the four-digit code they shared with you. You’ll play their game with the same settings.', icon: 'gamepad' },
    error: { label: 'Let’s get you back', title: 'A little out of tune', description: 'Try again, or head back to the games for a fresh start.', icon: 'arrows-rotate' },
};

export function dialog({ message = '', phase = '', opponentReady = false, code = '', join = false, ready = false, cancel = false, leave = false, exit = false, idle = false, seconds = 10, error = '' } = {}) {
    const root = document.getElementById('duel-modal');
    phase ||= join ? 'join' : idle ? 'idle' : code ? 'lobby' : error ? 'error' : 'connecting';
    const copy = phases[phase] || { label: 'Multiplayer duel', title: message, description: '', icon: 'music' };
    root.dataset.duelPhase = phase;
    root.querySelector('[data-duel-label]').textContent = copy.label;
    root.querySelector('[data-duel-message]').textContent = copy.title;
    const description = phase === 'error' && message ? message : copy.description;
    root.querySelector('[data-duel-description]').textContent = description;
    root.querySelector('[data-duel-description]').hidden = !description;
    root.querySelector('[data-duel-symbol]').className = `fa-solid fa-${copy.icon}`;
    root.querySelector('[data-duel-code]').textContent = code;
    root.querySelector('[data-duel-code]').hidden = !code;
    const participants = root.querySelector('[data-duel-participants]');
    participants.hidden = !['ready', 'waiting-ready', 'countdown'].includes(phase);
    for (const [role, isReady] of [['you', phase !== 'ready'], ['opponent', opponentReady || phase === 'countdown']]) {
        const card = root.querySelector(`[data-duel-participant="${role}"]`);
        card.dataset.ready = String(isReady);
        card.querySelector('[data-duel-participant-status]').textContent = isReady ? 'Ready ✓' : 'Getting ready';
    }
    const countdown = root.querySelector('[data-duel-countdown]');
    countdown.hidden = phase !== 'countdown';
    const value = root.querySelector('[data-duel-countdown-value]');
    if (value.textContent !== message) value.textContent = message;
    root.querySelector('[data-duel-join-form]').hidden = !join;
    root.querySelector('[data-duel-ready]').hidden = !ready;
    root.querySelector('[data-duel-cancel]').hidden = !cancel;
    root.querySelector('[data-duel-leave]').hidden = !leave;
    root.querySelector('[data-duel-exit]').hidden = !exit;
    root.querySelector('[data-duel-idle]').hidden = !idle;
    root.querySelector('[data-duel-idle-count]').textContent = String(seconds);
    root.querySelector('[data-duel-active]').hidden = !idle;
    root.querySelector('[data-duel-actions]').hidden = !(ready || cancel || leave || exit || idle);
    const errorElement = root.querySelector('[data-duel-error]');
    errorElement.hidden = !error;
    $(errorElement).text(error).toggle(Boolean(error));
    root.querySelector('.btn-close').hidden = !join;
    // Escape, backdrop clicks and dismiss controls must not strand an ended game.
    $(root).off('hide.bs.modal.duelExit').on('hide.bs.modal.duelExit', event => {
        if (['opponent-left', 'you-left', 'cancelled', 'expired'].includes(phase) && !root.dataset.duelClosing) {
            event.preventDefault();
        }
    });
    $(root).modal('show');
    return root;
}
export function closeDialog() {
    const root = document.getElementById('duel-modal');
    root.dataset.duelClosing = 'true';
    try { $(root).modal('hide'); }
    finally { delete root.dataset.duelClosing; }
}
