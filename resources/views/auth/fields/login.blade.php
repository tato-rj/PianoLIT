@input(['type' => 'email', 'name' => 'email', 'placeholder' => 'Email', 'autocomplete' => 'email'])
@input(['type' => 'password', 'name' => 'password', 'placeholder' => 'Password', 'autocomplete' => 'current-password', 'grid' => ($passwordRecovery ?? false) ? 'mb-0' : null])
@if($passwordRecovery ?? false)
<div class="auth-secondary text-end mt-2 mb-3">
    <a class="auth-secondary-link" href="{{ route('password.request') }}">Forgot password?</a>
</div>
@endif
