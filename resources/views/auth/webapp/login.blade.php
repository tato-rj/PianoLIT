@component('auth.webapp.layout', [
	'title' => 'Log in to PianoLIT',
	'subtitle' => 'If you already have a profile on our iOS app, you can use the same email and password to log in from here.'])

    <form method="POST" action="{{ route('login') }}" disable-on-submit>
        @csrf

        @include('auth.fields.login', ['passwordRecovery' => true])

        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary d-block w-100">Login</button>
        </div>
    </form>
    @include('auth.google.button')
    <p class="auth-secondary text-muted text-center mt-4 mb-0">
        Don't have an account? <a class="auth-secondary-link text-nowrap" href="{{ route('register') }}">Sign up</a>
    </p>
@endcomponent
