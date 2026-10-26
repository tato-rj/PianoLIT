<div class="access-prompt">
    <h2 id="{{ $headingId }}" class="h4 mb-2" data-access-title>{{ $title }}</h2>
    <p id="{{ $descriptionId }}" class="text-muted mb-4" data-access-description>@if(($kind ?? 'signup') === 'signup' && count($freeCopy = explode('free', $description, 2)) === 2){{ $freeCopy[0] }}<strong>free</strong>{{ $freeCopy[1] }}@else{{ $description }}@endif</p>
    @include('components.account.access-benefits')
    <a href="{{ $actionUrl ?? (($kind ?? 'signup') === 'premium' ? route('webapp.membership.pricing') : route('register')) }}" class="btn btn-primary w-100" @if($preview ?? false) data-ds-preview-action @endif>
        @if(($kind ?? 'signup') === 'premium')
            Go Premium
        @else
            Sign up <strong>free</strong>
        @endif
    </a>
    @if(($kind ?? 'signup') === 'signup')
        <p class="small text-muted text-center mb-0 mt-3">Already have an account? <a href="{{ ($preview ?? false) ? '#' : route('login') }}">Log in</a></p>
    @else
        <p class="small text-muted text-center mb-0 mt-3">Choose your plan. Cancel anytime.</p>
    @endif
</div>
