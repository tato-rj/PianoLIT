<section id="duel-results-overlay" data-duel-results hidden aria-labelledby="duel-result-title" data-result-tier="strong" data-result-variant="0">
    <div class="results-sheet">
        <div class="results-topline">
            <span class="results-game">{{$settings->gameName()}}</span>
            <span class="text-muted">Duel results</span>
        </div>

        <div class="results-greeting duel-result-greeting">
            <p class="duel-result-outcome fw-bold" data-duel-outcome aria-live="polite"></p>
            <h1 id="duel-result-title" tabindex="-1" data-duel-result-title></h1>
            <p data-duel-result-message></p>
        </div>

        @include('games.challenges.components.results.box', ['name' => 'score', 'title' => 'Your final score', 'icon' => 'bolt'])

        <div class="result-metric duel-comparison">
            <table aria-label="Duel results comparison">
                <thead><tr><th scope="col"><span class="visually-hidden">Result</span></th><th scope="col" class="text-blue">You</th><th scope="col">Opponent</th></tr></thead>
                <tbody>
                    @foreach(['score' => 'Points', 'accuracy' => 'Accuracy', 'rounds' => 'Rounds', 'time' => 'Time'] as $metric => $label)
                    <tr><th scope="row">{{$label}}</th><td data-duel-metric="you-{{$metric}}">—</td><td data-duel-metric="opponent-{{$metric}}">—</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="duel-result-rule text-muted" data-duel-result-rule></p>
        <div class="results-actions duel-result-actions">
            <div class="btn-floating" data-duel-result-rematch hidden>
                <button type="button" class="btn btn-primary w-100" data-duel-rematch>@include('games.challenges.components.icon', ['icon' => 'rotate-right'])<span data-duel-rematch-label>Play again</span></button>
            </div>
            <div class="btn-floating" data-duel-result-home hidden>
                <a href="{{route('games.home')}}" class="btn btn-white w-100">@include('games.challenges.components.icon', ['icon' => 'gamepad'])Back to all games</a>
            </div>
            <div class="btn-floating" data-duel-result-leave>
                <button type="button" class="btn btn-white w-100" data-duel-leave>@include('games.challenges.components.icon', ['icon' => 'right-from-bracket'])Leave Duel</button>
            </div>
        </div>
        <p class="text-danger" data-duel-rematch-error role="alert" hidden></p>
    </div>
</section>
