<section aria-label="Chord possibilities" data-chord-results>
    @include('tools.chord-finder.results.chords')
    <div class="border-top border-bottom py-3 mb-3">
        <div class="row g-3 align-items-center">
            <div class="col-sm-4">
                <h3 class="h4 mb-2" id="chord-playing-title"></h3>
                <div id="chord-label" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="col-sm-8">
                <div id="chord-output-piano" role="img" aria-label="Selected notes">
                    <div inert aria-hidden="true">
                        @include('components.music.piano.keyboard', ['centered' => true, 'responsive' => true, 'octaves' => [3 => [], 4 => []]])
                    </div>
                </div>
            </div>
        </div>
        <p id="chord-result-audio-status" class="small text-muted mt-3 mb-0" role="status" hidden></p>
    </div>
    <div id="info-container">
        @foreach($request['chords'] as $chord)
            @foreach($chord['inversions'] as $inversion)
                @php($analysisNotes = $inversion['analysis_chord'] ?? $inversion['chord'])
                <section data-chord-explanation style="display: none;" id="{{ $inversion['id'] }}" aria-label="Chord explanation: {{ strip_tags($inversion['label']['full_shorthand']) }}">
                    <div class="row g-3">
                        <div class="col-sm-4 text-start">
                            <h3 class="h6 mb-2">Intervals from the root</h3>
                            <ul class="list-unstyled small mb-0">
                                @foreach($inversion['intervals'] as $interval)
                                    <li class="border-bottom py-2">
                                        <span class="text-muted">{{ chordToHumans($analysisNotes[0]) }} → {{ chordToHumans($analysisNotes[$loop->iteration]) }}</span>
                                        <strong class="d-block mt-1">{{ $interval['name'] }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                            @foreach($inversion['label']['omissions'] ?? [] as $omitted)
                                <p class="small text-muted mt-2 mb-0">{{ $omitted['note'] }} ({{ $omitted['degree'] === 3 ? '3rd' : '5th' }}) omitted.</p>
                            @endforeach
                        </div>
                        @include('tools.chord-finder.results.labels.accordion', ['index' => $inversion['id'], 'chordlabel' => $inversion['label']])
                    </div>
                </section>
            @endforeach
        @endforeach
    </div>
</section>
