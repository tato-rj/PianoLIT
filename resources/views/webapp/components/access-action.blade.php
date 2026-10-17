@php($nextMemberState = isset($accessFeature) ? (app(\App\Services\WebApp\AccessPolicy::class)->snapshot()['rules']['account'][$accessFeature]['member'] ?? 'blocked') : 'available')
@guest('web')
    <a href="{{ route('register') }}" class="btn btn-primary me-2">Sign up free</a>
    <a href="{{ route('login') }}" class="btn btn-secondary">Sign in</a>
@else
    @if(!auth('web')->user()->hasActiveSubscription() && $nextMemberState === 'available')
        <a href="{{ route('webapp.membership.pricing') }}" class="btn btn-primary">Go Premium</a>
    @else
        <p class="small text-muted mb-0">This feature is currently unavailable for your account.</p>
    @endif
@endguest
