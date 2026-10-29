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
    <div class="quiz-gif-frame mx-auto">
        <span class="quiz-gif-fallback" aria-hidden="true">{{ $tier === 'oops' ? '🙈' : ($tier === 'perfect' ? '🏆' : '🎉') }}</span>
        <img class="quiz-gif" data-quiz-gif src="{{ str_replace('/giphy.gif', '/giphy_s.gif', $feedback['gif']) }}" data-animated-src="{{ $feedback['gif'] }}" alt="{{ $tier === 'oops' ? 'A little comic relief for that round' : 'Your well-earned victory dance' }}" width="480" height="270" referrerpolicy="no-referrer">
    </div>
    <div class="d-flex justify-content-center align-items-center gap-3 mt-2">
        <span class="small text-muted">GIPHY</span>
        <button type="button" class="btn btn-secondary btn-sm" data-quiz-gif-toggle hidden>Play GIF</button>
    </div>
</div>
