<section aria-label="Chord possibilities" data-chord-results>
    @include('tools.chord-finder.results.chords')
    <div class="border-top pt-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div><h3 class="h3 mb-2" id="chord-playing-title"></h3><div id="chord-label" class="d-flex flex-wrap gap-2"></div></div>
            <button type="button" class="btn btn-primary btn-sm" data-chord-play aria-label="Play chord" disabled>
                <span data-chord-play-label>@icon('play')Play chord</span>
                <span data-chord-stop-label hidden>@icon('square')Stop playback</span>
            </button>
        </div>
        <p id="chord-result-audio-status" class="small text-muted mt-3 mb-0" role="status" hidden></p>
    </div>
    <div id="info-container">
        @foreach($request['chords'] as $chord)
            @foreach($chord['inversions'] as $inversion)
                <section class="chord-info" style="display: none;" id="{{ $inversion['id'] }}" aria-label="Chord explanation: {{ strip_tags($inversion['label']['full_shorthand']) }}">
                    <div class="row gy-4">
                        <div class="col-md-4 text-start">
                            <h3 class="h6 mb-3">Intervals from the root</h3>
                            <ul class="list-unstyled small mb-0">
                                @foreach($inversion['intervals'] as $interval)
                                    <li class="border-bottom py-3">
                                        <span class="text-muted">{{ chordToHumans($inversion['chord'][0]) }} → {{ chordToHumans($inversion['chord'][$loop->iteration]) }}</span>
                                        <strong class="d-block mt-1">{{ $interval['name'] }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @include('tools.chord-finder.results.labels.accordion', ['index' => $inversion['id'], 'chordlabel' => $inversion['label']])
                    </div>
                </section>
            @endforeach
        @endforeach
    </div>
</section>
