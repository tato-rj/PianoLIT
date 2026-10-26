import { seededRandom } from './random';

const HISTORY_FIELDS = ['_stats', '_correctStreak', '_madeAnyMistake', '_targetSequence', '_lastTargetSignature',
    '_lastTargetName', '_previousAnswerIds', '_roundRecords', '_correctTaps', '_wrongTaps'];

const engines = {
    staff: { generation: 'newChallenge', progress: '_updateProgressBar', reset: '_resetProgress', score: 'points' },
    'tone-trek': { generation: '_startRound', progress: '_updateProgressBar', reset: '_resetRunUi', score: '_points', round: '_currentRound' },
    'beat-hero': { generation: '_startRound', progress: '_updateProgress', reset: '_resetGameUi', score: '_pointsValue', round: '_round' },
    'note-python': { generation: '_showStandardGameUi', progress: '_updateProgressBar', score: '_pointsValue', round: '_roundsCompleted' },
};

export function connectGame(game, duel, { finishOnly = false } = {}) {
    const engine = engines[duel.state.game] || engines.staff;
    const initial = duel.you();
    let completed = initial.progress;
    let earnedScore = initial.score;
    let initialized = false;
    let history = duel.state.checkpoint || {};
    const freshHistory = JSON.parse(JSON.stringify(Object.fromEntries(
        HISTORY_FIELDS.filter(key => key in game).map(key => [key, game[key]])
    )));
    const checkpoint = () => JSON.parse(JSON.stringify({
        ...Object.fromEntries(HISTORY_FIELDS.filter(key => key in game).map(key => [key, game[key]])),
        ...(game.staff?.getClef ? { _duelClef: game.staff.getClef() } : {}),
    }));

    const restore = () => {
        game[engine.score] = earnedScore;
        if (engine.round) game[engine.round] = completed + (engine.round === '_roundsCompleted' ? 0 : 1);
        game.$points?.text(String(earnedScore));
        game.$progressBar?.data('progress', completed * 100 / duel.state.total).css({ width: `${completed * 100 / duel.state.total}%` });
        game.$progressCounter?.text(`${completed} of ${duel.state.total}`);
        if (['treble', 'bass', 'alto', 'tenor'].includes(history._duelClef)) game.staff?.setClef(history._duelClef);
        for (const key of HISTORY_FIELDS) if (Object.hasOwn(history, key) && key in game) game[key] = history[key];
        game._finalStartMs = Date.parse(duel.state.starts_at);
        game._startedAt = Date.parse(duel.state.starts_at);
    };

    const checkMethod = duel.state.game === 'beat-hero' ? '_handleCardTap' : duel.state.game === 'note-python' ? '_advanceSnake' : '_onCheck';
    if (typeof game[checkMethod] === 'function') {
        const check = game[checkMethod].bind(game);
        const counts = () => duel.state.game === 'beat-hero'
            ? [game._correctTaps + game._wrongTaps, game._correctTaps]
            : [game._stats.checksTotal, game._stats.checksCorrect];
        game[checkMethod] = (...args) => {
            const before = counts();
            const incomplete = duel.state.game === 'tone-trek' && !game._roundLocked && !game._areAllBlockInputsFilled();
            const report = result => {
                const after = counts();
                if (after[0] > before[0] || incomplete) duel.answer(after[1] > before[1]);
                return result;
            };
            const result = check(...args);
            // Beat Hero counts answer taps synchronously; awaiting its preview audio
            // would accidentally include another tap's counters in this check.
            return report(result);
        };
    }

    if (engine.reset && game[engine.reset]) {
        const original = game[engine.reset].bind(game);
        game[engine.reset] = (...args) => { const result = original(...args); restore(); return result; };
    }
    const generate = game[engine.generation].bind(game);
    game[engine.generation] = (...args) => {
        if (!initialized) { restore(); initialized = true; }
        game._duelRandom = seededRandom(duel.state.seed, completed);
        game._duelChallengeRandom = seededRandom(duel.state.seed, `music:${completed}`);
        return generate(...args);
    };
    const progress = game[engine.progress].bind(game);
    game[engine.progress] = (...args) => {
        const result = progress(...args);
        const next = Math.round((Number(game.$progressBar.data('progress')) || 0) * duel.state.total / 100);
        if (next > completed) {
            completed = next;
            // Reset before generating the next challenge, independent of effects or hints.
            game._duelRandom = seededRandom(duel.state.seed, completed);
            game._duelChallengeRandom = seededRandom(duel.state.seed, `music:${completed}`);
            queueMicrotask(() => {
                earnedScore = Math.max(0, Number(game[engine.score]) || 0);
                history = checkpoint();
                duel.progress(next, earnedScore, history);
            });
        }
        return result;
    };
    if (duel.state.game === 'tone-trek') {
        const timedOut = game._finishRoundAsTimedOut.bind(game);
        game._finishRoundAsTimedOut = (...args) => {
            // Tone Trek advances its round on skip without advancing its single-player bar.
            game._madeAnyMistake = true;
            if (completed < game._currentRound) game._updateProgressBar();
            return timedOut(...args);
        };
    }
    if (duel.state.game === 'note-python') {
        const start = game.start.bind(game);
        game.start = () => {
            initialized = false;
            start();
            restore();
            // Both the initial start and a crash restart retain the Duel's earned rounds.
            game._setPlayButtons(true);
            game.$playWrap.hide();
            game._placeInitialSnake();
            game._directionQueue = [];
            game._spawnFoods(2, { preferredRow: game._rows - 2 });
            if (game._showBombs()) game._spawnBombs(2);
            game._ensureTargetFoodPresent();
            game._renderEntities();
            game._startLoop();
        };
    }
    game._restartDuel = () => {
        completed = 0;
        earnedScore = 0;
        initialized = false;
        history = {};
        Object.assign(game, JSON.parse(JSON.stringify(freshHistory)));
        game.$doublePoints?.hide?.();
        game.$bonusBadge?.hide?.();
        game.start();
    };
    if (finishOnly) {
        restore();
        // Reuse the engine's accuracy and bonus calculation if refresh interrupted results.
        game._showFinalResults();
    } else game.start();
    return game;
}
