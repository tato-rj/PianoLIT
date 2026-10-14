@extends(request()->getHost() === 'my.'.config('app.short_url') ? 'webapp.layouts.app' : 'layouts.app', ['title' => 'Connected Accounts'])
@section('content')
<div class="container">
  @if(request()->getHost() === 'my.'.config('app.short_url'))
    @include('webapp.layouts.header', ['title' => 'Connected Accounts', 'subtitle' => 'Manage how you sign in to PianoLIT'])
  @else
    <h2 class="text-center my-4">Connected Accounts</h2>
  @endif
  <div class="row"><div class="col-lg-8 col-12 mx-auto">
    <section class="bg-white border rounded p-4 mb-4" aria-label="Google connection">
      <div class="d-flex align-items-center gap-2 mb-2">@include('auth.google.logo')<h5 class="mb-0">Google{{ $google ? ' — Connected' : '' }}</h5></div>
      @if($google)
      <p class="text-muted text-break">{{ $google->provider_email }}</p>
      @if(!$canDisconnectGoogle)<p>Google is your only available sign-in method. Set a password before disconnecting it.</p><a href="{{ route('google.password') }}" class="btn btn-primary mb-3">Set a Password</a>@endif
      <form method="POST" action="{{ route('google.disconnect') }}" disable-on-submit>
        @csrf @method('DELETE')
        <button class="btn btn-secondary" type="submit" @if(!$canDisconnectGoogle) disabled @endif>Disconnect</button>
      </form>
      @else
      <p class="text-muted">Sign in quickly using your Google account.</p>
      @if($available)
      <form method="POST" action="{{ route('google.connect') }}" disable-on-submit>@csrf<button class="btn btn-secondary" type="submit">Connect Google</button></form>
      @else
      <p class="text-muted mb-0">Google sign-in is currently unavailable. Your email/password login still works.</p>
      @endif
      @endif
    </section>
    <section class="bg-white border rounded p-4 mb-4" aria-label="Apple connection">
      <div class="d-flex align-items-center gap-2 mb-2">@include('auth.apple.logo')<h5 class="mb-0">Apple{{ $apple ? ' — Connected' : '' }}</h5></div>
      @if($apple)
      @if($apple->provider_email)<p class="text-muted text-break">{{ $apple->provider_email }}
        @if(\Illuminate\Support\Str::endsWith(strtolower($apple->provider_email), '@privaterelay.appleid.com'))<span class="badge bg-light text-muted ms-2">Private email</span>@endif
      </p>@else<p class="text-muted">Connected to your Apple account.</p>@endif
      @if(!$canDisconnectApple)<p>Apple is your only available sign-in method. Set a password before disconnecting it.</p><a href="{{ route('google.password') }}" class="btn btn-primary mb-3">Set a Password</a>@endif
      <form method="POST" action="{{ route('apple.disconnect') }}" disable-on-submit>
        @csrf @method('DELETE')
        <button class="btn btn-secondary" type="submit" @if(!$canDisconnectApple) disabled @endif>Disconnect</button>
      </form>
      @else
      <p class="text-muted">Sign in quickly using your Apple account.</p>
      @if($appleAvailable)
      <form method="POST" action="{{ route('apple.connect') }}" disable-on-submit>@csrf<button class="btn btn-secondary" type="submit">Connect Apple</button></form>
      @else
      <p class="text-muted mb-0">Apple sign-in is currently unavailable. Your other sign-in methods are unchanged.</p>
      @endif
      @endif
    </section>
    @if(!$hasPassword && ($canDisconnectApple || $canDisconnectGoogle))
    <p>You can also <a href="{{ route('google.password') }}">set a PianoLIT password</a> to sign in using the existing iOS app.</p>
    @endif
    <p class="mb-4"><a href="{{ request()->getHost() === 'my.'.config('app.short_url') ? route('webapp.settings') : route('users.profile') }}">Back to settings</a></p>
  </div></div>
</div>
@endsection
