<div class="col-md-8">
    <h3 class="h6 mb-3">How we named this chord</h3>
    @component('components.accordion', ['id' => 'chord-accordion-'.$inversion['id']])
        @foreach(['root', 'type', 'sus', 'seventh', 'others', 'bass'] as $view)
            @include('tools.chord-finder.results.labels.'.$view, ['index' => $inversion['id'], 'step' => $loop->iteration, 'chordlabel' => $chordlabel])
        @endforeach
    @endcomponent
</div>
