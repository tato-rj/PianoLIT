<section class="project-deck" data-project-deck aria-label="Explore our projects">
    <div class="project-deck__viewport" data-project-viewport>
        @foreach($projects as $project)
            @include('components.project-card', ['project' => $project, 'cardPrefix' => $deckId ?? 'home-project'])
        @endforeach
    </div>
</section>
@once
    @push('scripts')
        <script src="{{ asset(mix('js/components/project-deck.js')) }}" defer></script>
    @endpush
@endonce
