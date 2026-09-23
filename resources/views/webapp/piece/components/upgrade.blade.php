@component('components.modal', ['id' => 'piece-upgrade-modal'])
    @slot('header')
        Go Premium
    @endslot
    @slot('body')
        <div class="text-center pb-3">
            <p>Subscribe to enjoy full videos, audio recordings, and readable scores.</p>
            <a href="{{ route('webapp.membership.pricing') }}" class="btn btn-primary rounded-pill btn-wide">@fa(['icon' => 'crown'])GO PREMIUM</a>
        </div>
    @endslot
@endcomponent
