<div class="form-group">
    <input required type="email" name="email" placeholder="Email" class="form-control w-100 input-light" value="{{ old('email') }}">
</div>
<div class="form-group">
    <input required type="password" name="password" placeholder="Password" class="form-control w-100 input-light" value="{{ old('password') }}">
    @if($passwordRecovery ?? false)
    <div class="auth-secondary text-end mt-2">
        <a class="auth-secondary-link" href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    @endif
</div>
