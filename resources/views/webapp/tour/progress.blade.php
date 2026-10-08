<div class="match-progress" data-tour-progress>
    <h1 id="match-tour-heading" class="visually-hidden">Find your match</h1>
    <ol class="match-progress-track" aria-label="Discovery progress">
        @foreach(['Your level', 'Listening', 'Sight-reading', 'Your taste', 'Mood', 'We found your perfect match!'] as $label)
        <li data-progress-step="{{ $loop->index }}" class="{{ $loop->first ? 'is-current' : '' }}" @if($loop->first) aria-current="step" @endif><span class="match-progress-dot"></span><span class="visually-hidden">{{ $label }}</span></li>
        @endforeach
    </ol>
    <p class="match-progress-label mb-0"><span data-step-number>1 / 5</span><span data-step-name>Your level</span></p>
</div>
