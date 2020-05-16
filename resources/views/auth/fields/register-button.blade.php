@env('production')
@include('auth.fields.recaptcha')
@endenv

<button disable-on-submit type="submit" class="btn btn-primary shadow btn-block mb-2">Register</button>
<div class="mb-4">
	<small>By continuing you indicate that you've read and agree to our <a href="{{route('terms')}}" target="_blank" class="link-blue">Terms of Service</a> and <a href="{{route('privacy')}}" target="_blank" class="link-blue">Privacy Policy</a>.</small>
</div>