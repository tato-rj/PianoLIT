<section class="up-hero up-card" aria-labelledby="up-name">
    <span class="up-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1)) }}</span>
    <div class="up-identity">
        <span class="up-eyebrow">ACCOUNT #{{ $user->id }}</span>
        <h2 id="up-name">{{ trim($user->full_name) }}</h2>
        <a class="up-email" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
        <div class="up-pills">
            <span class="up-pill">@icon($user->email_verified_at ? 'circle-check' : 'mail', ['mr' => 1, 'filled' => (bool) $user->email_verified_at, 'color' => $user->email_verified_at ? 'green' : null]){{ $user->email_verified_at ? 'Email confirmed' : 'Email not confirmed' }}</span>
            @if($user->location && $user->location->countryName)
            <span class="up-pill">@if($user->location->countryCode)@flag(['code' => $user->location->countryCode])@endif{{ $user->location->countryName }}</span>
            @endif
        </div>
    </div>
    <div class="up-hero-access"><span class="up-access-badge" data-profile-status data-base-label="{{ $billingStatus }}">{{ $status }}</span><strong data-profile-access data-billing-access="{{ $billingAccess ? '1' : '0' }}">{{ $hasAccess ? 'Full media access' : 'Preview access' }}</strong><small>Joined {{ optional($user->created_at)->format('M j, Y') ?: 'date not recorded' }}</small></div>
</section>
<div class="up-metrics">
    @foreach([['favorites', 'Saved pieces', 'heart', '#up-favorites'], ['folders', 'Collections', 'folder', '#up-favorites'], ['scores', 'Scores with markings', 'square-pen', '#up-favorites'], ['requests', 'Synthesia requests', 'video', '#up-requests']] as [$key, $label, $icon, $anchor])
    <a class="up-card up-metric" href="{{ $anchor }}"><span class="up-icon">@icon($icon, ['mr' => 0])</span><span>{{ $label }}<strong>{{ number_format($counts[$key]) }}</strong></span></a>
    @endforeach
</div>
