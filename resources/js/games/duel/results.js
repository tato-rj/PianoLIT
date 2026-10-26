import { chooseResultVariant } from '../games/shared/resultVariants';
import { GameAudio } from '../games/shared/GameAudio';

// Both browsers use the same saved results. Finishing first never decides the winner.
export function duelOutcome(state) {
    if (state.status !== 'finished' || state.players.length !== 2 || state.players.some(player => !player.finished_at || !player.result)) {
        return { kind: 'waiting', winner: null, reason: null };
    }
    const [a, b] = state.players;
    const points = a.score - b.score;
    const accuracy = a.result.accuracy - b.result.accuracy;
    const difference = points || accuracy;
    const winner = difference > 0 ? a.role : difference < 0 ? b.role : null;
    return { kind: winner ? (winner === state.role ? 'win' : 'loss') : 'draw', winner,
        reason: points ? 'points' : accuracy ? 'accuracy' : 'draw' };
}

export function duelDuration(startsAt, finishedAt) {
    if (!startsAt || !finishedAt) return '—';
    const seconds = Math.max(0, Math.floor((Date.parse(finishedAt) - Date.parse(startsAt)) / 1000));
    if (!Number.isFinite(seconds)) return '—';
    return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}

const greetings = {
    win: { tier: 'excellent', label: 'Duel champion', title: 'You won!!!', message: 'YES! You did it! Take a bow — that victory is yours! ✨' },
    loss: { tier: 'encouraging', label: 'Opponent won this round', title: 'Your comeback starts here!', message: 'This one went to your opponent. Keep playing, keep learning — your next win is waiting.' },
    draw: { tier: 'strong', label: 'A shared victory', title: 'It’s a draw!', message: 'Same points. Same accuracy. Two musicians, one brilliant match!' },
    waiting: { tier: 'strong', label: 'Your part is done ✓', title: 'Beautiful finish!', message: 'Your opponent is still playing. Stay here for the final results!' },
};

export function renderDuelResults(root, state) {
    if (!root) return;
    const outcome = duelOutcome(state);
    const greeting = greetings[outcome.kind];
    const changed = root.dataset.outcome !== outcome.kind;
    // The game wrapper is inert after finishing. Mount outside it so result actions work.
    if (root.parentElement !== document.body) document.body.append(root);
    root.hidden = false;
    document.body.classList.add('duel-results-open');
    const text = (selector, value) => { root.querySelector(selector).textContent = value; };
    if (changed) {
        if (root.dataset.outcome === 'waiting' && outcome.kind !== 'waiting' && state.options?.sound !== false) {
            try { GameAudio.playFinalResults(); } catch (_) {}
        }
        root.dataset.outcome = outcome.kind;
        root.dataset.resultTier = greeting.tier;
        root.dataset.resultVariant = chooseResultVariant(greeting.tier);
        text('[data-duel-outcome]', greeting.label);
        text('[data-duel-result-title]', greeting.title);
        text('[data-duel-result-message]', greeting.message);
        root.querySelector('[data-duel-result-title]').focus({ preventScroll: true });
        root.scrollTop = 0;
        const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        const confetti = window.Confetti || window.confetti;
        if (outcome.kind === 'win' && !reducedMotion && typeof confetti === 'function') {
            confetti({ particleCount: 150, spread: 85, origin: { y: .6 }, zIndex: 1001,
                colors: ['#ffe54c', '#55b9ac', '#b18ce8', '#f49236'] });
        }
    }
    const you = state.players.find(player => player.role === state.role);
    text('span[name="score"]', you.score);
    root.style.setProperty('--result-score-digits', String(Math.max(3, String(you.score).length)));
    for (const player of state.players) {
        const row = player.role === state.role ? 'you' : 'opponent';
        for (const [metric, value] of Object.entries({
            score: player.score,
            accuracy: player.result ? `${player.result.accuracy}%` : 'Still playing…',
            rounds: `${player.progress} / ${state.total}`,
            time: duelDuration(state.starts_at, player.finished_at),
        })) text(`[data-duel-metric="${row}-${metric}"]`, value);
    }
    const waiting = outcome.kind === 'waiting';
    text('[data-duel-result-rule]', waiting ? 'The winner is revealed when you both finish.' :
        outcome.reason === 'accuracy' ? 'Points tied — higher accuracy wins.' :
        outcome.reason === 'draw' ? 'Equal points and accuracy. You share the honors!' :
        'Highest final score wins. Accuracy breaks a tie.');
    root.querySelector('[data-duel-result-home]').hidden = waiting;
    root.querySelector('[data-duel-result-leave]').hidden = !waiting;
    root.querySelector('[data-duel-result-rematch]').hidden = waiting;
    root.querySelector('[data-duel-rematch]').disabled = !!you.rematch;
    text('[data-duel-rematch-label]', you.rematch ? 'Waiting for opponent…' :
        state.players.some(player => player.role !== state.role && player.rematch) ? 'Opponent wants to play again · Play again' : 'Play again');
}
