<section id="duel-hud" class="duel-hud mx-auto" hidden aria-label="Duel progress">
    <p class="duel-match-status text-center mb-3" data-duel-status>Connecting…</p>
    @foreach(['you' => 'You', 'opponent' => 'Opponent'] as $row => $label)
    <div class="duel-player-row duel-player-row--{{ $row }}" data-duel-row="{{ $row }}">
        <span class="fw-bold" data-duel-name>{{ $label }}</span>
        <div class="progress border"><div class="progress-bar" data-duel-bar style="width: 0%" role="progressbar" aria-label="{{ $label }} progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div></div>
        <span data-duel-count>0 / 0</span>
        <span class="text-primary fw-bold" data-duel-score>ϟ 0</span>
    </div>
    @endforeach
    <p class="small text-muted text-center mt-2" data-duel-connection aria-live="polite" hidden></p>
    @include('games.challenges.components.results.duel')
</section>
