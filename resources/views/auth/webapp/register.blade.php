@component('auth.webapp.layout', [
	'title' => 'Sign up for free', 
	'subtitle' => 'Access your profile across multiple platforms and devices, including our WebApp'])

    <form method="POST" action="{{ route('register') }}" id="register-form" disable-on-submit>
        @csrf

        @include('auth.fields.register')

        <div class="form-group text-center">
            @include('auth.fields.register-button')
            <p class="text-muted mb-0">Already have an account? <a class="fw-semibold text-decoration-underline text-nowrap" href="{{ route('login') }}">Log in</a></p>
        </div>
    </form>
    @include('auth.google.button')
@endcomponent
