@php
$duelConfig = config('games-duels.client') + [
    'base' => route('games.duels.store'),
    'home' => route('games.home'),
];
@endphp
<script>
window.__duelConfig = @json($duelConfig);
window.__duelState = @json($duelState ?? null);
</script>
@component('games.challenges.components.modal', ['title' => 'Multiplayer Duel', 'id' => 'duel-modal', 'class' => 'duel'])
<div class="duel-dialog text-center">
    <div class="duel-dialog-hero">
        <div class="duel-dialog-emblem" aria-hidden="true"><span class="duel-dialog-spark">✦</span><i data-duel-symbol class="fa-solid fa-music"></i><span class="duel-dialog-note">♪</span></div>
        <p class="duel-dialog-label" data-duel-label></p>
        <h2 data-duel-message aria-live="polite"></h2>
        <p class="duel-dialog-description" data-duel-description></p>
    </div>
    <div class="duel-dialog-players" data-duel-participants hidden>
        @foreach(['you' => 'You', 'opponent' => 'Opponent'] as $role => $label)
        <div class="duel-dialog-player" data-duel-participant="{{$role}}" data-ready="false">
            <span class="duel-dialog-avatar" aria-hidden="true">{{$role === 'you' ? '♪' : '♫'}}</span>
            <div><strong>{{$label}}</strong><span data-duel-participant-status>Getting ready</span></div>
        </div>
        @endforeach
    </div>
    <div class="duel-dialog-countdown" data-duel-countdown hidden>
        <span data-duel-countdown-value aria-live="off"></span>
        <span class="duel-dialog-countdown-caption">Starting together in…</span>
    </div>
    <div data-duel-code class="duel-code" hidden></div>
    <p id="duel-error" data-duel-error class="duel-dialog-error" role="alert" hidden></p>
    <form data-duel-join-form hidden>
        <label id="duel-code-label" for="duel-join-code" class="mb-3">Enter your duel code</label>
        <div class="duel-code-inputs" role="group" aria-labelledby="duel-code-label">
            @for($digit = 0; $digit < 4; $digit++)
            <input id="{{ $digit === 0 ? 'duel-join-code' : 'duel-join-digit-'.($digit + 1) }}" data-duel-digit type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="{{ $digit === 0 ? 'one-time-code' : 'off' }}" class="form-control duel-code-input" required aria-label="Digit {{ $digit + 1 }} of 4" aria-describedby="duel-error">
            @endfor
        </div>
        <section class="duel-create-tip" aria-labelledby="duel-create-tip-title">
            <span class="duel-create-tip__icon" aria-hidden="true">@include('games.challenges.components.icon', ['icon' => 'gear', 'mr' => 0])</span>
            <div>
                <h5 id="duel-create-tip-title">Want to create a duel?</h5>
                <p>Open the settings menu in any game, then choose <strong>“Start Multiplayer Duel”</strong>.</p>
            </div>
        </section>
        <button type="submit" class="btn btn-primary w-100 mt-3">@include('games.challenges.components.icon', ['icon' => 'gamepad'])Join duel</button>
    </form>
    <div class="duel-dialog-idle" data-duel-idle hidden>
        <span data-duel-idle-count>10</span><span>seconds to stay in the duel</span>
    </div>
    <div class="duel-dialog-actions" data-duel-actions hidden>
        <button type="button" data-duel-active class="btn btn-primary w-100" hidden>@include('games.challenges.components.icon', ['icon' => 'check'])Yes, I’m still playing</button>
        <button type="button" data-duel-ready class="btn btn-primary w-100" hidden>@include('games.challenges.components.icon', ['icon' => 'bolt'])I’m ready</button>
        <button type="button" data-duel-cancel class="btn btn-white w-100" hidden>Cancel invitation</button>
        <button type="button" data-duel-leave class="btn btn-white w-100" hidden>@include('games.challenges.components.icon', ['icon' => 'right-from-bracket'])Leave duel</button>
        <a data-duel-exit href="{{ route('games.home') }}" class="btn btn-primary w-100" hidden>@include('games.challenges.components.icon', ['icon' => 'gamepad'])Back to all games</a>
    </div>
</div>
@endcomponent
