<footer class="site-footer container mt-auto py-4 pt-2 pt-md-4">
    <div class="row align-items-center gy-3 text-center">
        <div class="col-12 col-md-4 text-md-start">
            <p class="mb-0">Made for the love of piano.</p>
        </div>
        <nav class="col-12 col-md-4" aria-label="Footer">
            <div class="row justify-content-center gx-4">
                <div class="col-auto"><a href="{{ route('contact') }}">Support</a></div>
                <div class="col-auto"><a href="{{ route('privacy') }}">Privacy</a></div>
                <div class="col-auto"><a href="{{ route('terms') }}">Terms</a></div>
            </div>
        </nav>
        <div class="col-12 col-md-4 text-md-end">
            <small>© {{ date('Y') }} PianoLIT</small>
        </div>
    </div>
</footer>
