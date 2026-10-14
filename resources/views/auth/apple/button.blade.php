<a href="{{ route('apple.redirect') }}" data-apple-login class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 w-100" style="background-color: #000; color: #fff !important; border-color: #000;">
  @include('auth.apple.logo', ['white' => true])
  <span data-apple-label>Continue with Apple</span>
  <span data-apple-spinner class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
</a>
@once
@push('header')
<style>
[data-apple-login]:focus-visible { outline: 2px solid var(--color-primary) !important; outline-offset: 3px; }
</style>
@endpush
@push('scripts')
<script>
(function () {
  document.addEventListener('click', function (event) {
    var link = event.target.closest('[data-apple-login]');
    if (!link || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    if (link.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
    link.setAttribute('aria-busy', 'true');
    link.querySelector('[data-apple-label]').textContent = 'Opening Apple…';
    link.querySelector('[data-apple-spinner]').classList.remove('d-none');
  });
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-apple-login]').forEach(function (link) {
      link.removeAttribute('aria-busy');
      link.querySelector('[data-apple-label]').textContent = 'Continue with Apple';
      link.querySelector('[data-apple-spinner]').classList.add('d-none');
    });
  });
})();
</script>
@endpush
@endonce
