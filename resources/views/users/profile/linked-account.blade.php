<div class="profile-provider-row d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0">
            @include('auth.'.$provider.'.logo', ['logoSize' => 26, 'markOnly' => true])
        </span>
        <div class="profile-provider-details">
            <h4 class="h6 mb-1">{{ ucfirst($provider) }}</h4>
            <p class="small text-muted mb-0 text-break">{{ $connection['account'] ? ($connection['account']->provider_email ?: 'Connected') : 'Not connected' }}</p>
            @if($connection['account'])<span class="small text-success">Connected</span>@endif
        </div>
    </div>
    @if($connection['account'])
        <form method="POST" action="{{ route($provider.'.disconnect') }}" disable-on-submit>
            @csrf @method('DELETE')
            <button class="btn btn-secondary btn-sm" type="submit" @if(!$connection['canDisconnect']) disabled @endif aria-label="Disconnect {{ ucfirst($provider) }}">Disconnect</button>
        </form>
        @if(!$connection['canDisconnect'])<p class="small text-muted w-100 mb-0">Set a password before disconnecting your only sign-in method.</p>@endif
    @elseif($connection['available'])
        <form method="POST" action="{{ route($provider.'.connect') }}" disable-on-submit>
            @csrf
            <button class="btn btn-secondary btn-sm" type="submit">Connect {{ ucfirst($provider) }}</button>
        </form>
    @else
        <span class="small text-muted">Currently unavailable</span>
    @endif
</div>
