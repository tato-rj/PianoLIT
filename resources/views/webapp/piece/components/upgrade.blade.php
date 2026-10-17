@if(auth('web')->check() && !auth('web')->user()->hasActiveSubscription())
    @php($accessPrompts = app(\App\Services\WebApp\AccessPrompts::class))
    @include('components.access-modal', $accessPrompts->prompt('default', 'premium') + [
        'id' => 'piece-upgrade-modal', 'actionCopies' => $accessPrompts->premiumCopy(),
    ])
@endif
