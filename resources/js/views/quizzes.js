(function () {
    'use strict';
    const root = document.querySelector('[data-quiz]');
    if (!root) return;

    const questions = Array.from(root.querySelectorAll('[data-quiz-question]'));
    const introduction = root.querySelector('[data-quiz-introduction]');
    const content = root.querySelector('[data-quiz-content]');
    const start = root.querySelector('[data-quiz-start]');
    const next = root.querySelector('[data-quiz-next]');
    const results = root.querySelector('[data-quiz-results]');
    const feedback = root.querySelector('[data-quiz-feedback]');
    const status = root.querySelector('[data-quiz-result-status]');
    const actions = root.querySelector('[data-quiz-result-actions]');
    const retry = root.querySelector('[data-quiz-retry]');
    const progress = root.querySelector('[role="progressbar"]');
    const progressLabel = root.querySelector('[data-quiz-progress-label]');
    const streakLabel = root.querySelector('[data-quiz-streak]');
    const modal = root.querySelector('[data-quiz-modal]');
    const reveal = root.querySelector('[data-quiz-reveal]');
    const showResult = root.querySelector('[data-quiz-show-result]');
    const dialog = window.bootstrap.Modal.getOrCreateInstance(modal);
    const motion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    const animations = new Set();
    const previousPicks = {};
    let answers = questions.map(function () { return null; });
    let pending = false;
    let attempt = 0;
    let currentQuestion = 0;
    let streak = 0;
    let pauseGif = function () {};
    let autoRevealTimer = null;
    let revealTimer = null;
    let finishRevealWait = null;
    let afterClose = null;

    function reducedMotion() { return (motion && motion.matches) || document.hidden; }

    function pick(key, items) {
        const choices = items.filter(function (item) { return item !== previousPicks[key]; });
        const choice = choices[Math.floor(Math.random() * choices.length)];
        previousPicks[key] = choice;
        return choice;
    }

    function focus(element, scroll) {
        element.focus({preventScroll: true});
        if (scroll) element.scrollIntoView({behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start'});
    }

    function animate(element, frames, options, cleanup) {
        if (reducedMotion() || !element.animate) { if (cleanup) cleanup(); return; }
        const animation = element.animate(frames, options);
        animations.add(animation);
        function finish() { animations.delete(animation); if (cleanup) cleanup(); }
        animation.finished.then(finish, finish);
    }

    function clearEffects() {
        animations.forEach(function (animation) { animation.cancel(); });
        animations.clear();
        root.querySelectorAll('.quiz-particle').forEach(function (particle) { particle.remove(); });
    }

    function burst(host, finale, correct) {
        if (reducedMotion() || !host.animate) return;
        const glyphs = correct ? ['♪', '♫', '★', '●'] : ['♪', '?', '✦'];
        for (let i = 0; i < (finale ? 32 : 12); i++) {
            const particle = document.createElement('span');
            particle.className = 'quiz-particle';
            particle.setAttribute('aria-hidden', 'true');
            particle.textContent = glyphs[Math.floor(Math.random() * glyphs.length)];
            particle.style.color = ['#0055fe', '#a2398b', '#007a53', '#d5481c'][i % 4];
            particle.style.left = (finale ? 15 + Math.random() * 70 : 50) + '%';
            particle.style.top = finale ? '35%' : '65%';
            host.appendChild(particle);
            const x = (Math.random() - 0.5) * (finale ? 260 : 180);
            const y = -(60 + Math.random() * (finale ? 190 : 100));
            animate(particle, [
                {transform: 'translate(-50%, 0) scale(.4)', opacity: 0},
                {transform: 'translate(' + x * .5 + 'px, ' + y + 'px) rotate(' + x + 'deg) scale(1)', opacity: 1, offset: .45},
                {transform: 'translate(' + x + 'px, ' + (finale ? 130 : 35) + 'px) rotate(' + x * 2 + 'deg) scale(.6)', opacity: 0}
            ], {duration: (finale ? 1400 : 750) + Math.random() * 350, easing: 'ease-out'}, function () { particle.remove(); });
        }
    }

    function stopAudio() {
        root.querySelectorAll('audio').forEach(function (audio) {
            audio.pause();
            try { audio.currentTime = 0; } catch (error) { /* Media may not have loaded yet. */ }
        });
    }

    function updateProgress() {
        const completed = answers.filter(function (answer) { return answer !== null; }).length;
        progress.setAttribute('aria-valuenow', String(completed));
        progress.querySelector('.progress-bar').style.width = (questions.length ? completed / questions.length * 100 : 0) + '%';
        progressLabel.textContent = completed + ' / ' + questions.length;
        streakLabel.hidden = streak < 2;
        streakLabel.textContent = '🔥 ' + streak + ' in a row';
    }

    function showQuestion(index) {
        currentQuestion = index;
        stopAudio();
        clearEffects();
        questions.forEach(function (question, i) { question.hidden = i !== index; });
        next.hidden = true;
        const question = questions[index];
        animate(question, [{opacity: 0, transform: 'translateY(14px)'}, {opacity: 1, transform: 'translateY(0)'}], {duration: 260, easing: 'ease-out'});
        focus(question.querySelector('h2'));
    }

    function cancelScheduledReveal() {
        window.clearTimeout(autoRevealTimer);
        autoRevealTimer = null;
    }

    function scheduleResult() {
        cancelScheduledReveal();
        if (document.hidden || pending || !results.hidden || answers.includes(null) || !answers.length) return;
        const currentAttempt = attempt;
        autoRevealTimer = window.setTimeout(function () {
            autoRevealTimer = null;
            if (currentAttempt === attempt && !document.hidden) loadResult(true);
        }, reducedMotion() ? 350 : 650);
    }

    function cancelRevealPause() {
        window.clearTimeout(revealTimer);
        revealTimer = null;
        if (finishRevealWait) { finishRevealWait(); finishRevealWait = null; }
    }

    function revealPause(suspense) {
        if (!suspense || reducedMotion()) return Promise.resolve();
        return new Promise(function (resolve) {
            finishRevealWait = resolve;
            revealTimer = window.setTimeout(function () {
                revealTimer = null;
                finishRevealWait = null;
                resolve();
            }, 1100);
        });
    }

    function closeFor(action) {
        if (!modal.classList.contains('show')) { action(); return; }
        afterClose = action;
        dialog.hide();
    }

    function prepareGif() {
        const gif = feedback.querySelector('[data-quiz-gif]');
        if (!gif) return;
        const still = gif.getAttribute('src');
        let playing = false;
        let failed = false;
        function play(value) {
            playing = value && !failed;
            gif.src = playing ? gif.dataset.animatedSrc : still;
        }
        pauseGif = function () { if (playing) play(false); };
        gif.addEventListener('error', function () {
            if (failed) return;
            failed = true;
            gif.hidden = true;
        });
        play(!reducedMotion() && modal.classList.contains('show'));
        if (gif.complete && !gif.naturalWidth) { gif.hidden = true; failed = true; }
    }

    function prepareShare() {
        const panel = feedback.querySelector('[data-quiz-share-panel]');
        const open = feedback.querySelector('[data-quiz-share-open]');
        const score = feedback.querySelector('[data-quiz-score]');
        if (!panel || !open) return;
        open.hidden = false;
        if (window.PianoLITLinkShare) window.PianoLITLinkShare.init();
        function showSharing(sharing) {
            panel.hidden = !sharing;
            score.hidden = sharing;
            actions.hidden = sharing;
            open.setAttribute('aria-expanded', String(sharing));
            if (sharing) {
                pauseGif();
                clearEffects();
                focus(panel.querySelector('h2'));
                animate(panel.querySelector('img'), [{opacity: 0, transform: 'scale(.94) rotate(-2deg)'}, {opacity: 1, transform: 'scale(1) rotate(0)'}], {duration: 450, easing: 'ease-out'});
            } else focus(open);
            dialog.handleUpdate();
        }
        open.addEventListener('click', function () { showSharing(true); });
        panel.querySelector('[data-quiz-share-back]').addEventListener('click', function () { showSharing(false); });
        const native = panel.querySelector('[data-quiz-share-native]');
        if (typeof window.navigator.share === 'function') {
            native.hidden = false;
            native.addEventListener('click', async function () {
                if (native.disabled) return;
                native.disabled = true;
                const error = panel.querySelector('[data-share-error]');
                error.hidden = true;
                try {
                    await window.navigator.share({title: panel.dataset.shareTitle, text: panel.dataset.shareTitle, url: panel.dataset.shareUrl});
                } catch (failure) {
                    if (failure.name !== 'AbortError') {
                        panel.querySelector('[data-share-error-message]').textContent = 'Choose a social button or copy your link instead.';
                        error.hidden = false;
                    }
                } finally { native.disabled = false; }
            });
        }
    }

    function animateComparison() {
        feedback.querySelectorAll('[data-quiz-compare-fill]').forEach(function (bar, index) {
            animate(bar, [{transform: 'scaleX(0)'}, {transform: 'scaleX(1)'}], {
                duration: 850, delay: 180 + index * 170, easing: 'cubic-bezier(.16, 1, .3, 1)', fill: 'backwards'
            });
        });
        const medal = feedback.querySelector('[data-quiz-award]');
        if (medal) animate(medal, [
            {transform: 'scale(.3) rotate(-100deg)', opacity: 0},
            {transform: 'scale(1.16) rotate(12deg)', opacity: 1, offset: .7},
            {transform: 'scale(1) rotate(0)', opacity: 1}
        ], {duration: 750, delay: 350, fill: 'backwards', easing: 'ease-out'});
    }

    async function loadResult(suspense) {
        if (pending || !answers.length || answers.includes(null)) return;
        pending = true;
        const currentAttempt = attempt;
        next.hidden = true;
        cancelScheduledReveal();
        results.hidden = false;
        results.setAttribute('aria-busy', 'true');
        actions.hidden = true;
        retry.hidden = true;
        status.setAttribute('role', 'status');
        status.textContent = 'Cue the drumroll…';
        reveal.hidden = false;
        feedback.innerHTML = '';
        showResult.hidden = false;
        dialog.show();
        try {
            const responses = await Promise.all([
                window.axios.get(root.dataset.url, {params: {answers: answers.slice()}, timeout: 15000}),
                revealPause(suspense)
            ]);
            if (currentAttempt !== attempt) return;
            const response = responses[0];
            if (typeof response.data !== 'string' || !response.data.includes('data-quiz-score')) throw new Error('Missing quiz result');
            feedback.innerHTML = response.data;
            status.textContent = '';
            actions.hidden = false;
            const score = feedback.querySelector('[data-quiz-score]');
            results.dataset.quizTier = score ? score.dataset.quizTier : 'good';
            prepareGif();
            prepareShare();
            if (modal.classList.contains('show')) {
                animate(feedback, [{opacity: 0, transform: 'scale(.9) translateY(18px)'}, {opacity: 1, transform: 'scale(1.025)'}, {opacity: 1, transform: 'scale(1)'}], {duration: 650, easing: 'ease-out'});
                burst(results, true, results.dataset.quizTier !== 'oops');
                animateComparison();
            }
        } catch (error) {
            if (currentAttempt !== attempt) return;
            status.setAttribute('role', 'alert');
            status.textContent = 'Your answers are still here. Let’s try that result again.';
            retry.hidden = false;
        } finally {
            if (currentAttempt === attempt) {
                cancelRevealPause();
                reveal.hidden = true;
                pending = false;
                results.setAttribute('aria-busy', 'false');
                dialog.handleUpdate();
            }
        }
    }

    if (start) start.addEventListener('click', function () {
        introduction.hidden = true;
        content.hidden = false;
        if (questions.length) showQuestion(0);
    });

    questions.forEach(function (question, index) {
        const buttons = Array.from(question.querySelectorAll('[data-quiz-answer]'));
        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (answers[index] !== null || button.disabled || question.hidden) return;
                answers[index] = Number(button.dataset.quizAnswer);
                stopAudio();
                const correct = buttons.find(function (answer) { return answer.hasAttribute('correct'); });
                const won = button === correct;
                streak = won ? streak + 1 : 0;
                question.dataset.quizOutcome = won ? 'correct' : 'incorrect';
                buttons.forEach(function (answer) {
                    answer.disabled = true;
                    answer.setAttribute('aria-pressed', answer === button ? 'true' : 'false');
                    const outcome = answer.querySelector('[data-quiz-answer-outcome]');
                    if (answer === correct) {
                        answer.classList.add('is-correct');
                        outcome.hidden = false;
                        outcome.textContent = '✓ Correct';
                    } else if (answer === button) {
                        answer.classList.add('is-incorrect');
                        outcome.hidden = false;
                        outcome.textContent = '✕ Oops';
                    }
                });
                const reaction = question.querySelector('[data-quiz-question-feedback]');
                const line = won
                    ? (streak >= 2 ? '🔥 ' + streak + ' in a row! ' + pick('streak', ['You’re on fire', 'Save some for the encore', 'No brakes on this piano']) : pick('correct', ['Nailed it! 🎯', 'Brain: standing ovation 👏', 'That’s how it’s done ✨', 'Take a tiny bow 🎹']))
                    : pick('incorrect', ['A very confident plot twist 🙃', 'Blame the imaginary cat 🐈', 'We’ll call that jazz 🙈', 'Your brain took a coffee break ☕']);
                reaction.textContent = (won ? 'Correct! ' : 'Not quite! ') + line;
                // Keep the correction available to assistive technology without a paragraph of duplicate copy.
                reaction.setAttribute('aria-label', reaction.textContent + (won || !correct ? '' : '. Correct answer: ' + correct.querySelector('.quiz-answer-copy').textContent));
                animate(button, won
                    ? [{transform: 'scale(1)'}, {transform: 'scale(1.035) rotate(-1deg)'}, {transform: 'scale(.99) rotate(1deg)'}, {transform: 'scale(1)'}]
                    : [{transform: 'translateX(0)'}, {transform: 'translateX(-8px) rotate(-1deg)'}, {transform: 'translateX(7px) rotate(1deg)'}, {transform: 'translateX(-4px)'}, {transform: 'translateX(0)'}],
                {duration: won ? 480 : 400, easing: 'ease-out'});
                animate(reaction, [{transform: 'translateY(8px) scale(.92)', opacity: 0}, {transform: 'translateY(0) scale(1)', opacity: 1}], {duration: 360});
                burst(question, false, won);
                updateProgress();
                if (index === questions.length - 1) {
                    next.hidden = true;
                    scheduleResult();
                } else {
                    next.hidden = false;
                    // Pointer answers stay in place. Keyboard activation can continue on Next without scrolling.
                    if (event.detail === 0) next.focus({preventScroll: true});
                }
            });
        });
    });

    next.addEventListener('click', function () {
        if (answers[currentQuestion] === null || next.hidden) return;
        if (currentQuestion < questions.length - 1) showQuestion(currentQuestion + 1);
    });
    retry.addEventListener('click', function () { loadResult(false); });
    showResult.addEventListener('click', function () { dialog.show(); });
    modal.addEventListener('shown.bs.modal', function () {
        // Bootstrap ignores hide() during its opening transition; finish a quick replay/review click afterward.
        if (afterClose) dialog.hide();
    });
    modal.addEventListener('hide.bs.modal', function () {
        cancelRevealPause();
        clearEffects();
        pauseGif();
    });
    modal.addEventListener('hidden.bs.modal', function () {
        if (afterClose) {
            const action = afterClose;
            afterClose = null;
            action();
        } else if (!showResult.hidden) showResult.focus({preventScroll: true});
    });
    root.querySelector('[data-quiz-review]').addEventListener('click', function () {
        closeFor(function () {
            stopAudio();
            pauseGif();
            clearEffects();
            questions.forEach(function (question) { question.hidden = false; });
            if (questions.length) focus(questions[0].querySelector('h2'), true);
        });
    });
    root.querySelector('[data-quiz-reset]').addEventListener('click', function () {
        closeFor(function () {
            attempt += 1;
            cancelScheduledReveal();
            cancelRevealPause();
            pending = false;
            streak = 0;
            answers = questions.map(function () { return null; });
            pauseGif();
            pauseGif = function () {};
            clearEffects();
            questions.forEach(function (question) {
                delete question.dataset.quizOutcome;
                const reaction = question.querySelector('[data-quiz-question-feedback]');
                reaction.textContent = '';
                reaction.removeAttribute('aria-label');
                question.querySelectorAll('[data-quiz-answer]').forEach(function (button) {
                    button.disabled = false;
                    button.setAttribute('aria-pressed', 'false');
                    button.classList.remove('is-correct', 'is-incorrect');
                    button.querySelector('[data-quiz-answer-outcome]').hidden = true;
                });
            });
            results.hidden = true;
            reveal.hidden = true;
            showResult.hidden = true;
            results.setAttribute('aria-busy', 'false');
            delete results.dataset.quizTier;
            feedback.innerHTML = '';
            status.textContent = '';
            actions.hidden = true;
            retry.hidden = true;
            updateProgress();
            if (questions.length) showQuestion(0);
        });
    });
    function suspend() { clearEffects(); pauseGif(); stopAudio(); }
    if (motion && motion.addEventListener) motion.addEventListener('change', function () { if (motion.matches) { suspend(); cancelRevealPause(); } });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { suspend(); cancelScheduledReveal(); }
        else scheduleResult();
    });
    window.addEventListener('pagehide', function () { suspend(); cancelScheduledReveal(); cancelRevealPause(); dialog.hide(); });
})();
