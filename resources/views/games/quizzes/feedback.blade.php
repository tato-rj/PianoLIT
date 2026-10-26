<div class="quiz-score" data-quiz-score>
    <p class="small text-muted mb-2">Quiz complete</p>
    <h2 class="h3 mb-2">{{ $feedback['score'] }} out of {{ $feedback['questions_count'] }}</h2>
    <p class="text-muted mb-0">{{ $feedback['percentage'] == 100 ? 'Every answer correct. Nicely done!' : ($feedback['percentage'] >= 50 ? 'Well done. Revisit the answers below and keep discovering.' : 'Every question is a chance to learn. Review the answers below and give it another try.') }}</p>
</div>
