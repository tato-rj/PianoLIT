@if(auth('web')->check() && !auth('web')->user()->hasActiveSubscription())
    @include('components.account.access-modal', app(\App\Services\WebApp\AccessPrompts::class)->prompt('collections', 'premium') + ['id' => 'collection-upgrade-modal'])
@endif
