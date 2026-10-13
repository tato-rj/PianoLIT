@component('auth.webapp.layout', [
	'title' => 'Log in to PianoLIT',
	'subtitle' => 'If you already have a profile on our iOS app, you can use the same email and password to log in from here.'])

    <form method="POST" action="{{ route('login') }}" disable-on-submit>
        @csrf

        @include('auth.fields.login')

        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary shadow d-block w-100 mb-4">Login</button>
            <p class="mb-2"><a class="link-none" href="{{ route('register') }}">Don't have an account yet?</a></p>
            <p class="mb-0"><a class="link-none" href="{{ route('password.request') }}">Forgot Your Password?</a></p>
        </div>
    </form>
    @include('auth.google.button')
@endcomponent
