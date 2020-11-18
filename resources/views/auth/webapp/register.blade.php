@component('auth.webapp.layout', [
	'title' => 'Sign up for free', 
	'subtitle' => 'Access your profile across multiple platforms and devices, including our WebApp',
	'animated' => false])

    <form method="POST" action="{{ route('register') }}" id="register-form" disable-on-submit>
        @csrf

        @include('auth.fields.register')

        <div class="form-group text-center">
            @include('auth.fields.register-button')
            <h6><a class="link-none" href="{{ route('login') }}">Already have an account?</a></h6>
        </div>
    </form>
@endcomponent