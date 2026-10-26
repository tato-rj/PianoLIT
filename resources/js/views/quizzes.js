(function () {
    'use strict';
    const root = document.querySelector('[data-quiz]');
    if (!root) return;

    const questions = Array.from(root.querySelectorAll('[data-quiz-question]'));
    const introduction = root.querySelector('[data-quiz-introduction]');
    const content = root.querySelector('[data-quiz-content]');
    const start = root.querySelector('[data-quiz-start]');
    const results = root.querySelector('[data-quiz-results]');
    const feedback = root.querySelector('[data-quiz-feedback]');
    const status = root.querySelector('[data-quiz-result-status]');
    const actions = root.querySelector('[data-quiz-result-actions]');
    const retry = root.querySelector('[data-quiz-retry]');
    const progress = root.querySelector('[role="progressbar"]');
    const progressLabel = root.querySelector('[data-quiz-progress-label]');
    let answers = questions.map(function () { return null; });
    let pending = false;
    let attempt = 0;

    function focus(element) {
        element.focus({preventScroll: true});
        const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        element.scrollIntoView({behavior: reduced ? 'auto' : 'smooth', block: 'start'});
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
        progressLabel.textContent = completed + ' of ' + questions.length + ' answered';
    }

    async function loadResult() {
        if (pending || !answers.length || answers.includes(null)) return;
        pending = true;
        const currentAttempt = attempt;
        results.hidden = false;
        results.setAttribute('aria-busy', 'true');
        actions.hidden = true;
        retry.hidden = true;
        status.setAttribute('role', 'status');
        status.textContent = 'Loading your result…';
        feedback.innerHTML = '';
        focus(results);
        try {
            const response = await window.axios.get(root.dataset.url, {params: {answers: answers.slice()}});
            if (currentAttempt !== attempt) return;
            if (typeof response.data !== 'string' || !response.data.includes('data-quiz-score')) throw new Error('Missing quiz result');
            feedback.innerHTML = response.data;
            status.textContent = '';
            actions.hidden = false;
        } catch (error) {
            if (currentAttempt !== attempt) return;
            status.setAttribute('role', 'alert');
            status.textContent = 'Your result could not be loaded. Your answers are still here; please try again.';
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
        if (questions.length) focus(questions[0].querySelector('h2'));
    });

    questions.forEach(function (question, index) {
        const buttons = Array.from(question.querySelectorAll('[data-quiz-answer]'));
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (answers[index] !== null || button.disabled) return;
                answers[index] = Number(button.dataset.quizAnswer);
                stopAudio();
                const correct = buttons.find(function (answer) { return answer.hasAttribute('correct'); });
                buttons.forEach(function (answer) {
                    answer.disabled = true;
                    answer.setAttribute('aria-pressed', answer === button ? 'true' : 'false');
                    const outcome = answer.querySelector('[data-quiz-answer-outcome]');
                    if (answer === correct) {
                        answer.classList.add('is-correct');
                        outcome.hidden = false;
                        outcome.textContent = answer === button ? 'Your answer · correct' : 'Correct answer';
                    } else if (answer === button) {
                        answer.classList.add('is-incorrect');
                        outcome.hidden = false;
                        outcome.textContent = 'Your answer';
                    }
                });
                question.querySelector('[data-quiz-question-feedback]').textContent = button === correct
                    ? 'Correct. Nicely done!' : 'The correct answer is ' + (correct ? correct.querySelector('.quiz-answer-copy').textContent : 'shown above') + '.';
                updateProgress();
                if (!answers.includes(null)) loadResult();
            });
        });
    });

    retry.addEventListener('click', loadResult);
    root.querySelector('[data-quiz-review]').addEventListener('click', function () {
        if (questions.length) focus(questions[0].querySelector('h2'));
    });
    root.querySelector('[data-quiz-reset]').addEventListener('click', function () {
        attempt += 1;
        pending = false;
        answers = questions.map(function () { return null; });
        stopAudio();
        questions.forEach(function (question) {
            question.querySelector('[data-quiz-question-feedback]').textContent = '';
            question.querySelectorAll('[data-quiz-answer]').forEach(function (button) {
                button.disabled = false;
                button.setAttribute('aria-pressed', 'false');
                button.classList.remove('is-correct', 'is-incorrect');
                button.querySelector('[data-quiz-answer-outcome]').hidden = true;
            });
        });
        results.hidden = true;
        results.setAttribute('aria-busy', 'false');
        feedback.innerHTML = '';
        status.textContent = '';
        actions.hidden = true;
        retry.hidden = true;
        updateProgress();
        if (questions.length) focus(questions[0].querySelector('h2'));
    });
})();
