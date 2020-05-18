@component('auth.webapp.layout', ['title' => 'Sign up for free', 'subtitle' => 'Use the form below to sign up and get started. Enjoy!'])
    <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf

        @include('auth.fields.register')

        <div class="form-group text-center">
            @include('auth.fields.register-button')
            <h6><a class="link-none" href="{{ route('login') }}">Already have an account?</a></h6>
        </div>
    </form>
@endcomponent