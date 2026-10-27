<section class="project-deck" data-project-deck aria-label="Explore PianoLIT worlds">
    <div class="row g-2">
        @foreach($projects as $project)
            @php($compact = in_array($project['key'], ['shop', 'metronome', 'blog']))
            <div class="{{ $compact ? 'col-12 col-md-4' : 'col-12 col-sm-6 col-lg-3' }}">
                @include('home.components.project-card', ['project' => $project, 'compact' => $compact, 'cardPrefix' => $deckId ?? 'home-project'])
            </div>
        @endforeach
    </div>
</section>
@once
    @push('scripts')
        <script src="{{ asset(mix('js/components/project-deck.js')) }}" defer></script>
    @endpush
@endonce
