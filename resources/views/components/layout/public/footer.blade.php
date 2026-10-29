<footer class="site-footer container mt-auto py-4 pt-2 pt-md-4">
    @if($homeWorlds ?? false)
    <div class="row align-items-center justify-content-between gy-2 mx-lg-4">
        <div class="col-12 col-sm-auto"><p class="mb-0">Made for the love of piano.</p></div>
        <div class="col-12 col-sm-auto"><p class="mb-0">PianoLIT · Come back curious.</p></div>
    </div>
    @else
    <nav class="text-center mx-lg-4 mb-3" aria-label="PianoLIT hubs">
        <ul class="row justify-content-center gx-4 gy-2 list-unstyled mb-0">
            @foreach([
                ['label' => 'Web app', 'url' => route('webapp.discover'), 'newTab' => true],
                ['label' => 'iOS app', 'url' => config('app.stores.ios'), 'newTab' => true],
                ['label' => 'Learn', 'url' => route('learn.home')],
                ['label' => 'Games', 'url' => route('games.home')],
                ['label' => 'Shop', 'url' => route('shop.home')],
                ['label' => 'Metronome', 'url' => route('metronome.home'), 'newTab' => true],
                ['label' => 'Blog', 'url' => route('posts.index')],
            ] as $footerHub)
                <li class="col-auto">
                    <a href="{{ $footerHub['url'] }}" @if($footerHub['newTab'] ?? false) target="_blank" rel="noopener noreferrer" @endif>{{ $footerHub['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="row align-items-center gy-3 text-center mx-lg-4">
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
    @endif
</footer>
