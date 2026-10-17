@if(auth('web')->check() && !auth('web')->user()->hasActiveSubscription())
    @component('components.modal', ['id' => 'collection-upgrade-modal', 'header' => 'Explore every collection', 'centered' => true, 'descriptionId' => 'collection-upgrade-description'])
        @slot('body')
            <p id="collection-upgrade-description" class="text-muted">Become a member to explore every piece in every collection and find more music for your next practice session.</p>
            <a href="{{ route('webapp.membership.pricing') }}" class="btn btn-primary w-100">@icon('crown')GO PREMIUM</a>
        @endslot
    @endcomponent
@endif
