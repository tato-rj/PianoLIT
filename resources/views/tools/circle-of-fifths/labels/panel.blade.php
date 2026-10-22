<div class="tab-pane fade{{ $mode === 'major' ? ' show active' : '' }}" id="mode-{{ $mode }}" role="tabpanel" aria-labelledby="{{ $mode }}-tab" tabindex="0">
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="bg-light rounded-sm p-3 h-100">
                @include('tools.circle-of-fifths.labels.title', ['title' => 'key'])
                <h3 class="key-name h5 mb-0" aria-live="polite"></h3>
            </div>
        </div>
        <div class="col-6">
            <div class="bg-light rounded-sm p-3 h-100">
                @include('tools.circle-of-fifths.labels.title', ['title' => 'relative key'])
                <p class="key-relative fw-semibold mb-0"></p>
            </div>
        </div>
    </div>
    <div class="mb-4">
        @include('tools.circle-of-fifths.labels.title', ['title' => 'key signature'])
        <img class="key-signature" data-folder="{{ asset('images/misc/keys/') }}" src="{{ asset('images/misc/keys/key-loading.svg') }}" alt="Key signature" width="180" height="80">
        <p class="key-signature-note small text-muted d-none mb-0">This spelling uses double accidentals.</p>
    </div>
    <div class="mb-4">
        @include('tools.circle-of-fifths.labels.title', ['title' => 'closely related keys'])
        <div class="{{ $mode === 'enharmonic' ? 'key-enharmonic-neighbors' : 'key-neighbors' }} d-flex flex-wrap gap-2"></div>
    </div>
    <div>
        @include('tools.circle-of-fifths.labels.title', ['title' => 'functional harmony'])
        <div class="key-roman key-{{ $mode }}-roman d-flex flex-wrap gap-2 mb-3"></div>
        <div class="row g-2">
            @foreach(['tonic' => 'Tonic', 'dominant' => 'Dominant', 'subdominant' => 'Subdominant'] as $function => $label)
                <div class="col-4">
                    <div class="bg-light rounded-sm py-2 px-1 h-100">
                        <p class="small text-muted mb-2">{{ $label }}</p>
                        <div class="key-{{ $function }} small"></div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($mode === 'minor')
            <p class="small text-muted mt-3 mb-0">* Dominant chords use the raised seventh (the leading tone). This makes V major, III augmented, and VII diminished.</p>
        @endif
    </div>
</div>
