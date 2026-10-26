<div class="result-metric result-metric--{{$name}}">
    @if($name === 'score')
        <div class="result-score-burst" aria-hidden="true">
            <svg viewBox="0 0 200 200" fill="none" focusable="false">
                <path d="m100 5 13 39 32-25-5 42 42-5-25 32 39 12-39 13 25 32-42-5 5 42-32-25-13 39-12-39-32 25 5-42-42 5 25-32-39-13 39-12-25-32 42 5-5-42 32 25Z" fill="currentColor"/>
            </svg>
        </div>
        @include('games.challenges.components.results.character')
        <span class="result-score-spark result-score-spark--one" aria-hidden="true">✦</span>
        <span class="result-score-spark result-score-spark--two" aria-hidden="true">✦</span>
    @endif
    <div class="result-metric__label"><span class="result-metric__icon" aria-hidden="true">@fa</span>{{$title}}</div>
    <div class="result-metric__value"><span name="{{$name}}">{{$name === 'duration' ? '00:00' : '0'}}{{$name === 'accuracy' ? '%' : ''}}</span></div>
    @if($name === 'score')
        <div class="result-metric__detail">points</div>
    @endif
</div>
