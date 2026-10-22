@component('auth.webapp.layout', [
    'showCarousel' => false,
    'title' => 'Reset your password below',
    'subtitle' => 'Enter your email address and choose a new password.'])

    <form method="POST" action="{{ route('password.update') }}" disable-on-submit>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        @input(['type' => 'email', 'name' => 'email', 'placeholder' => 'Email', 'autocomplete' => 'email'])
        @input(['type' => 'password', 'name' => 'password', 'placeholder' => 'New password', 'autocomplete' => 'new-password'])
        @input(['type' => 'password', 'name' => 'password_confirmation', 'placeholder' => 'Confirm your new password', 'autocomplete' => 'new-password'])

        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary d-block w-100">Reset my password</button>
        </div>
    </form>
    <p class="auth-secondary text-muted text-center mt-4 mb-0">
        <a class="auth-secondary-link" href="{{ route('login') }}">Back to log in</a>
    </p>
@endcomponent
