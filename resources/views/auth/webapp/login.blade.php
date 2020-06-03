@component('auth.webapp.layout', [
	'title' => 'Log in to PianoLIT', 
	'subtitle' => 'If you already have a profile on our iOS app, you can use the same email and password to log in from here.',
	'animated' => true])

    <form method="POST" action="{{ route('login') }}" disable-on-submit>
        @csrf
        
        @include('auth.fields.login')

        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary shadow btn-block mb-4">Login</button>
            <h6><a class="link-none" href="{{ route('register') }}">Don't have an account yet?</a></h6>
            <h6><a class="link-none" href="{{ route('password.request') }}">Forgot Your Password?</a></h6>
        </div>
    </form>
@endcomponent