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
    const motion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    const animations = new Set();
    const previousPicks = {};
    let answers = questions.map(function () { return null; });
    let pending = false;
    let attempt = 0;
    let currentQuestion = 0;
    let streak = 0;
    let pauseGif = function () {};

    function reducedMotion() { return (motion && motion.matches) || document.hidden; }

    function pick(key, items) {
        const choices = items.filter(function (item) { return item !== previousPicks[key]; });
        const choice = choices[Math.floor(Math.random() * choices.length)];
        previousPicks[key] = choice;
        return choice;
    }

    function focus(element) {
        element.focus({preventScroll: true});
        element.scrollIntoView({behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start'});
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

    function prepareGif() {
        const gif = feedback.querySelector('[data-quiz-gif]');
        const toggle = feedback.querySelector('[data-quiz-gif-toggle]');
        if (!gif || !toggle) return;
        const still = gif.getAttribute('src');
        let playing = false;
        let failed = false;
        function play(value) {
            playing = value && !failed;
            gif.src = playing ? gif.dataset.animatedSrc : still;
            toggle.textContent = playing ? 'Pause GIF' : 'Play GIF';
        }
        pauseGif = function () { if (playing) play(false); };
        gif.addEventListener('error', function () {
            if (failed) return;
            failed = true;
            gif.hidden = true;
            toggle.hidden = true;
        });
        toggle.hidden = false;
        toggle.addEventListener('click', function () { play(!playing); });
        play(!reducedMotion());
        if (gif.complete && !gif.naturalWidth) { gif.hidden = true; toggle.hidden = true; failed = true; }
    }

    async function loadResult() {
        if (pending || !answers.length || answers.includes(null)) return;
        pending = true;
        const currentAttempt = attempt;
        next.hidden = true;
        questions.forEach(function (question) { question.hidden = true; });
        results.hidden = false;
        results.setAttribute('aria-busy', 'true');
        actions.hidden = true;
        retry.hidden = true;
        status.setAttribute('role', 'status');
        status.textContent = 'Cue the drumroll…';
        feedback.innerHTML = '';
        focus(results);
        try {
            const response = await window.axios.get(root.dataset.url, {params: {answers: answers.slice()}});
            if (currentAttempt !== attempt) return;
            if (typeof response.data !== 'string' || !response.data.includes('data-quiz-score')) throw new Error('Missing quiz result');
            feedback.innerHTML = response.data;
            status.textContent = '';
            actions.hidden = false;
            const score = feedback.querySelector('[data-quiz-score]');
            results.dataset.quizTier = score ? score.dataset.quizTier : 'good';
            prepareGif();
            animate(feedback, [{opacity: 0, transform: 'scale(.9) translateY(18px)'}, {opacity: 1, transform: 'scale(1.025)'}, {opacity: 1, transform: 'scale(1)'}], {duration: 650, easing: 'ease-out'});
            burst(results, true, results.dataset.quizTier !== 'oops');
        } catch (error) {
            if (currentAttempt !== attempt) return;
            status.setAttribute('role', 'alert');
            status.textContent = 'Your answers are still here. Let’s try that result again.';
            retry.hidden = false;
        } finally {
            if (currentAttempt === attempt) {
                pending = false;
                results.setAttribute('aria-busy', 'false');
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
            button.addEventListener('click', function () {
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
                next.textContent = index === questions.length - 1 ? 'The big reveal →' : 'Next →';
                next.hidden = false;
                next.focus({preventScroll: true});
            });
        });
    });

    next.addEventListener('click', function () {
        if (answers[currentQuestion] === null || next.hidden) return;
        if (currentQuestion < questions.length - 1) showQuestion(currentQuestion + 1);
        else loadResult();
    });
    retry.addEventListener('click', loadResult);
    root.querySelector('[data-quiz-review]').addEventListener('click', function () {
        stopAudio();
        pauseGif();
        clearEffects();
        questions.forEach(function (question) { question.hidden = false; });
        if (questions.length) focus(questions[0].querySelector('h2'));
    });
    root.querySelector('[data-quiz-reset]').addEventListener('click', function () {
        attempt += 1;
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
        results.setAttribute('aria-busy', 'false');
        delete results.dataset.quizTier;
        feedback.innerHTML = '';
        status.textContent = '';
        actions.hidden = true;
        retry.hidden = true;
        updateProgress();
        if (questions.length) showQuestion(0);
    });
    function suspend() { clearEffects(); pauseGif(); stopAudio(); }
    if (motion && motion.addEventListener) motion.addEventListener('change', function () { if (motion.matches) suspend(); });
    document.addEventListener('visibilitychange', function () { if (document.hidden) suspend(); });
    window.addEventListener('pagehide', suspend);
})();
