@php
    $tier = $feedback['percentage'] == 100 ? 'perfect' : ($feedback['percentage'] >= 75 ? 'great' : ($feedback['percentage'] >= 50 ? 'good' : 'oops'));
    $lines = [
        'perfect' => ['Somebody book Carnegie Hall!', 'Leave some talent for the rest of us!', 'Even Beethoven is applauding'],
        'great' => ['One tiny step from a standing ovation', 'Your brain deserves an encore', 'Okay, maestro. We see you'],
        'good' => ['Half genius. Half jazz improvisation', 'A few wrong notes. Still a tune', 'The comeback tour starts here'],
        'oops' => ['That was… experimental jazz', 'Your lucky finger owes you an apology', 'Plot twist: that was the warm-up'],
    ];
@endphp
<div class="quiz-score text-center" data-quiz-score data-quiz-tier="{{ $tier }}">
    <p class="quiz-score-number mb-2"><span class="visually-hidden">{{ $feedback['score'] }} out of {{ $feedback['questions_count'] }}</span><span aria-hidden="true">{{ $feedback['score'] }}<span class="quiz-score-total"> / {{ $feedback['questions_count'] }}</span></span></p>
    <h2 class="h4 mb-3">{{ randval($lines[$tier]) }}</h2>
    <div class="quiz-finale-grid {{ !empty($comparison) ? 'quiz-finale-grid-compared' : '' }}">
    @if(!empty($comparison))
        @include('games.quizzes.comparison')
    @endif
    <div>
    <div class="quiz-gif-frame mx-auto">
        <span class="quiz-gif-fallback" aria-hidden="true">{{ $tier === 'oops' ? '🙈' : ($tier === 'perfect' ? '🏆' : '🎉') }}</span>
        <img class="quiz-gif" data-quiz-gif src="{{ str_replace('/giphy.gif', '/giphy_s.gif', $feedback['gif']) }}" data-animated-src="{{ $feedback['gif'] }}" alt="{{ $tier === 'oops' ? 'A little comic relief for that round' : 'Your well-earned victory dance' }}" width="480" height="270" referrerpolicy="no-referrer">
    </div>
    <p class="quiz-gif-credit mb-0 mt-1">GIF via GIPHY</p>
    </div>
    </div>
    @if(!empty($share))
        <button type="button" class="btn btn-primary btn-lg quiz-share-action mt-2" data-quiz-share-open aria-controls="quiz-share-panel" aria-expanded="false" hidden>@icon('share')Share my score</button>
    @endif
</div>

@if(!empty($share))
    @include('games.quizzes.share-panel')
@endif
