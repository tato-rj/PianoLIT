<section class="signup-card card rounded-sm shadow text-center" aria-labelledby="{{ $headingId }}" @if($preview ?? false) data-ds-preview @endif>
    <div class="signup-card__icon rounded-circle" aria-hidden="true">@icon('music', ['mr' => 0])</div>
    <h4 id="{{ $headingId }}" class="mb-2">{{ $title }}</h4>
    <p class="signup-card__description text-muted">{{ $description }}</p>
    <a href="{{ route('register') }}" class="signup-card__action btn btn-primary">Sign up free</a>
    <p class="signup-card__login text-muted"><span>Already have an account? <a href="{{ route('login') }}">Log in</a></span></p>
    <ul class="signup-card__benefits list-unstyled mb-0 text-muted" aria-label="Free account benefits">
        @foreach($benefits as $benefit)
            <li><span class="signup-card__benefit-icon rounded-circle" aria-hidden="true">@icon($benefit['icon'], ['mr' => 0])</span><span>{{ $benefit['label'] }}</span></li>
        @endforeach
    </ul>
</section>
