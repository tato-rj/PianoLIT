@if(app(\App\Auth\Google\Configuration::class)->available())
<div class="d-flex align-items-center gap-3 my-3 text-muted" aria-hidden="true"><span class="border-top flex-grow-1"></span><span>or</span><span class="border-top flex-grow-1"></span></div>
<a href="{{ route('google.redirect') }}" data-google-login class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 w-100">
  @include('auth.google.logo')
  <span data-google-label>Continue with Google</span>
  <span data-google-spinner class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
</a>
<p class="text-muted text-center mt-2 mb-3"><small>By continuing, you agree to our <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms</a> and <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.</small></p>
@once
@push('scripts')
<script>
(function () {
  document.addEventListener('click', function (event) {
    var link = event.target.closest('[data-google-login]');
    if (!link || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    if (link.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
    link.setAttribute('aria-busy', 'true');
    link.querySelector('[data-google-label]').textContent = 'Opening Google…';
    link.querySelector('[data-google-spinner]').classList.remove('d-none');
  });
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-google-login]').forEach(function (link) {
      link.removeAttribute('aria-busy');
      link.querySelector('[data-google-label]').textContent = 'Continue with Google';
      link.querySelector('[data-google-spinner]').classList.add('d-none');
    });
  });
})();
</script>
@endpush
@endonce
@endif
