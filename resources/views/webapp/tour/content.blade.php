<section id="match-tour" class="match-tour mx-auto" data-url="{{ route('webapp.tour.result') }}" aria-label="Find your match">
    @if($tour['ready'])
    <div class="match-count text-center">
        <div class="match-count-value"><span data-count>{{ number_format($tour['total']) }}</span> <span data-count-unit>pieces</span></div>
        <p class="text-muted small mb-0" data-count-note>In the PianoLIT library</p>
        <span class="visually-hidden" role="status" data-count-announcement></span>
    </div>
    <div class="d-flex justify-content-between match-navigation">
        <button type="button" class="btn btn-link text-muted ps-0" data-back disabled>← Back</button>
        <button type="button" class="btn btn-link text-muted pe-0" data-restart>Start over</button>
    </div>
    <div data-stage></div>
    <p role="alert" class="text-danger mt-3" data-error hidden></p>
    <noscript><p>Please enable JavaScript to find your match.</p></noscript>
    @else
    <div class="text-center py-5"><p>The listening tour is temporarily unavailable.</p><a href="{{ route('webapp.explore') }}" class="btn btn-primary rounded-pill">Explore the library</a></div>
    @endif
</section>
