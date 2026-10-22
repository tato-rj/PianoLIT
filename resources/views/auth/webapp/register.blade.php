@component('auth.webapp.layout', [
	'title' => 'Sign up for free', 
	'subtitle' => 'Access your profile across multiple platforms and devices, including our WebApp'])

    <form method="POST" action="{{ route('register') }}" id="register-form" disable-on-submit>
        @csrf

        @include('auth.fields.register')

        <div class="form-group text-center">
            @include('auth.fields.register-button')
        </div>
    </form>
    @include('auth.google.button')
    <p class="auth-secondary text-muted text-center mt-4 mb-0">
        Already have an account? <a class="auth-secondary-link text-nowrap" href="{{ route('login') }}">Log in</a>
    </p>
@endcomponent
