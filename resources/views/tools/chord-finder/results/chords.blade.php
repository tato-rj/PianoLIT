<div class="row g-3 mb-3">
@php
    $rankedInversions = collect($request['chords'])->flatMap(function ($chord) { return $chord['inversions']; })->sortByDesc('ranking');
    $hasOtherMatches = $rankedInversions->contains(function ($inversion) use ($request) { return $inversion['ranking'] < $request['most_relevant']; });
@endphp
@foreach(['best' => 'Best matches', 'other' => 'Other possibilities'] as $group => $heading)
    @php
        $matches = $rankedInversions->filter(function ($inversion) use ($request, $group) {
            return $group === 'best' ? $inversion['ranking'] === $request['most_relevant'] : $inversion['ranking'] < $request['most_relevant'];
        });
    @endphp
    @if($matches->isNotEmpty())
        <div class="{{ !$hasOtherMatches ? 'col-12' : ($group === 'best' ? 'col-auto col-sm-4' : 'col-auto col-sm-8') }}">
            <h3 class="h6 mb-2">{{ $heading }}</h3>
            <div class="chords-results d-flex flex-wrap gap-2">
                @foreach($matches as $inversion)
                    <button type="button" class="btn btn-secondary mw-100 d-flex align-items-center justify-content-between gap-3" href="#{{ $inversion['id'] }}" aria-label="Play {{ strip_tags($inversion['label']['full_shorthand']) }} chord" aria-pressed="false" aria-controls="{{ $inversion['id'] }}" data-chord-play data-notes="{{ json_encode($inversion['chord']) }}" data-bass="{{ $request['strict'] && $request['tool'] !== 'button' ? $inversion['label']['bass'] : '' }}">
                        <span data-chord-name class="h5 text-nowrap mb-0">{!! $inversion['label']['full_shorthand'] !!}</span>
                        <span data-chord-play-icon>@icon('play', ['mr' => 0])</span>
                        <span data-chord-stop-icon hidden>@icon('square', ['mr' => 0])</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
</div>
