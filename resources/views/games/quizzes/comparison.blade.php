@php
    $awards = [
        'above' => ['🏆', 'Beat the average!'],
        'tied' => ['🎯', 'Right on the beat!'],
        'below' => ['🚀', 'Comeback time!'],
        'first' => ['🌟', 'First on stage!'],
        'unavailable' => ['🎹', 'A score to chase!'],
    ];
    [$award, $awardLabel] = $awards[$comparison['standing']];
@endphp
<section class="quiz-comparison text-start" aria-label="Your score compared with earlier plays" data-quiz-comparison data-standing="{{ $comparison['standing'] }}">
    <div class="quiz-award d-flex align-items-center gap-2 mb-2">
        <span class="quiz-award-medal" data-quiz-award aria-hidden="true">{{ $award }}</span>
        <strong>{{ $awardLabel }}</strong>
    </div>
    <div class="d-flex justify-content-between align-items-baseline mb-1" id="quiz-your-score-label"><strong>You</strong><span><strong>{{ $feedback['score'] }}</strong> / {{ $feedback['questions_count'] }}</span></div>
    <div class="progress quiz-comparison-track mb-2" role="progressbar" aria-labelledby="quiz-your-score-label" aria-valuemin="0" aria-valuemax="{{ max(1, $feedback['questions_count']) }}" aria-valuenow="{{ $feedback['score'] }}" aria-valuetext="{{ $feedback['score'] }} out of {{ $feedback['questions_count'] }}">
        <div class="progress-bar quiz-comparison-fill quiz-comparison-fill-you" data-quiz-compare-fill style="width: {{ $comparison['score_percent'] }}%"></div>
    </div>
    <div class="d-flex justify-content-between align-items-baseline mb-1" id="quiz-average-score-label"><span>Average</span><span>{{ $comparison['average_label'] ?? '—' }}@if($comparison['average'] !== null)<span class="text-muted"> / {{ $feedback['questions_count'] }}</span>@endif</span></div>
    @if($comparison['average'] !== null)
        <div class="progress quiz-comparison-track" role="progressbar" aria-labelledby="quiz-average-score-label" aria-valuemin="0" aria-valuemax="{{ max(1, $feedback['questions_count']) }}" aria-valuenow="{{ $comparison['average'] }}" aria-valuetext="Average {{ $comparison['average_label'] }} out of {{ $feedback['questions_count'] }} from {{ $comparison['plays'] }} earlier plays">
            <div class="progress-bar quiz-comparison-fill quiz-comparison-fill-average" data-quiz-compare-fill style="width: {{ $comparison['average_percent'] }}%"></div>
        </div>
    @else
        <div class="progress quiz-comparison-track" aria-hidden="true"></div>
        <span class="visually-hidden">{{ $comparison['plays'] ? 'Earlier scores cannot be compared with this quiz version.' : 'No earlier plays yet.' }}</span>
    @endif
    <div class="quiz-play-count d-inline-flex align-items-center gap-2 mt-2">
        <span aria-hidden="true">👥</span><strong>{{ number_format($comparison['plays']) }}</strong><span>earlier {{ Str::plural('play', $comparison['plays']) }}</span>
    </div>
</section>
