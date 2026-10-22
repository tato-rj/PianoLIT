@include('auth.fields.recaptcha')

<button disable-on-submit type="submit" class="btn btn-primary d-block w-100 mb-2">Register</button>
<p class="auth-secondary text-muted text-center mb-4">
    By continuing you indicate that you've read and agree to our <a href="{{route('terms')}}" target="_blank" class="auth-secondary-link text-nowrap">Terms of Service</a> and <a href="{{route('privacy')}}" target="_blank" class="auth-secondary-link text-nowrap">Privacy Policy</a>.
</p>
