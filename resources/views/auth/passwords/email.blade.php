@component('auth.webapp.layout', [
    'showCarousel' => false,
    'title' => 'Forgot your password?',
    'subtitle' => "You will receive an email with a link to change your password. Just click on that link, type out your new password and that's it!"])

    <form method="POST" action="{{ route('password.email') }}" disable-on-submit>
        @csrf
        @input(['type' => 'email', 'name' => 'email', 'placeholder' => 'Email', 'autocomplete' => 'email'])

        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary d-block w-100">Send Password Reset Link</button>
        </div>
    </form>
    <p class="auth-secondary text-muted text-center mt-4 mb-0">
        <a class="auth-secondary-link" href="{{ route('login') }}">Back to log in</a>
    </p>
@endcomponent
