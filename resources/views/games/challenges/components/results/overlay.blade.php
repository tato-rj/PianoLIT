<div id="final-overlay" data-result-tier="excellent" data-result-variant="0" aria-labelledby="result-title">
    <div class="results-sheet">
        <div class="results-topline">
            @include('games.challenges.components.identity')
            <div class="btn-floating flex-shrink-0">
                <a href="{{route('games.home')}}" class="btn btn-white btn-sm text-nowrap">All games @include('games.challenges.components.icon', ['icon' => 'arrow-up-right-from-square', 'mr' => 0])</a>
            </div>
        </div>

        <div id="result-greeting" class="results-greeting">
            <h1 id="result-title" tabindex="-1"></h1>
        </div>

        <div id="metrics-boxes" class="results-metrics" aria-label="Your results">
            @include('games.challenges.components.results.box', ['name' => 'score', 'title' => 'Your score', 'icon' => 'bolt'])
            @include('games.challenges.components.results.box', ['name' => 'accuracy', 'title' => 'Accuracy', 'icon' => 'bullseye'])
            @include('games.challenges.components.results.box', ['name' => 'rounds', 'title' => 'Rounds', 'icon' => 'gamepad'])
            @include('games.challenges.components.results.box', ['name' => 'duration', 'title' => 'Time', 'icon' => 'clock'])
        </div>

        <div class="results-bonuses">
            <span id="double-points" class="results-bonus results-bonus--perfect" style="display: none">@include('games.challenges.components.icon', ['icon' => 'star'])Perfect · 2× score</span>
            <span id="settings-bonus-earned" class="results-bonus" style="display: none">@include('games.challenges.components.icon', ['icon' => 'gem'])Bonus points earned</span>
        </div>

        <div class="results-actions">
            <div class="btn-floating">
                <button type="button" onclick="location.reload();" class="btn btn-primary w-100">@include('games.challenges.components.icon', ['icon' => 'rotate-right'])Play again</button>
            </div>
            <div class="btn-floating">
                <button type="button" data-join-leaderboard data-bs-toggle="modal" data-bs-target="#save-results-modal" class="btn btn-white w-100">@include('games.challenges.components.icon', ['icon' => 'ranking-star'])Join the leaderboard</button>
            </div>
        </div>
    </div>
</div>

@include('games.challenges.components.leaderboard.store')
