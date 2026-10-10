<section id="match-tour" class="match-tour mx-auto" data-url="{{ route('webapp.tour.result') }}" aria-label="Find your match">
    @if($tour['ready'])
    <div class="match-count text-center">
        <div class="match-count-number">
            {{-- Keep SVG radius inline: the legacy CSS optimizer drops geometry properties. --}}
            <svg class="match-count-ring" aria-hidden="true" focusable="false">
                <circle class="match-count-arc" data-count-arc cx="50%" cy="50%" r="45.5%" pathLength="100" style="r: calc(50% - 4.5px)"></circle>
            </svg>
            <span data-count>{{ $tour['total'] >= 1000 ? '1k+' : $tour['total'] }}</span>
            <span class="match-count-unit" data-count-unit>pieces</span>
        </div>
        <span class="visually-hidden" data-count-note>In the PianoLIT library</span>
        <span class="visually-hidden" role="status" data-count-announcement></span>
    </div>
    <div data-stage></div>
    <p role="alert" class="text-danger text-center mt-3" data-error hidden></p>
    <nav class="match-navigation" aria-label="Question navigation">
        <button type="button" class="btn btn-link" data-back disabled>@icon('arrow-left', ['mr' => 1]) Back</button>
        <button type="button" class="btn btn-link" data-skip hidden><span data-skip-label>Skip</span> @icon('arrow-right', ['mr' => 0, 'ml' => 1])</button>
        <button type="button" class="btn btn-link" data-restart hidden>Start over @icon('rotate-ccw', ['mr' => 0, 'ml' => 1])</button>
    </nav>
    @else
    <div class="text-center py-5"><p>The listening tour is temporarily unavailable.</p><a href="{{ route('webapp.explore') }}" class="btn btn-primary rounded-pill">Explore the library</a></div>
    @endif
</section>
