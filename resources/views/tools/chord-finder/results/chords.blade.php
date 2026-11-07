<div class="row g-3 mb-4">
@foreach(['best' => 'Best matches', 'other' => 'Other possibilities'] as $group => $heading)
    @php
        $matches = collect($request['chords'])->flatMap(function ($chord) { return $chord['inversions']; })->filter(function ($inversion) use ($request, $group) {
            return $group === 'best' ? $inversion['ranking'] === $request['most_relevant'] : $inversion['ranking'] < $request['most_relevant'];
        })->sortByDesc('ranking');
    @endphp
    @if($matches->isNotEmpty())
        <div class="col-12 col-md">
            <div class="h-100 p-3 p-sm-4 {{ $group === 'best' ? 'bg-light rounded' : '' }}">
                <h3 class="{{ $group === 'best' ? 'h5' : 'h6' }} mb-3">{{ $heading }}</h3>
                <div class="chords-results d-flex flex-wrap gap-3">
                    @foreach($matches as $inversion)
                        <button type="button" class="btn btn-secondary d-flex align-items-center justify-content-between gap-4 {{ $group === 'best' ? 'chord-finder__match' : '' }}" href="#{{ $inversion['id'] }}" aria-label="View {{ strip_tags($inversion['label']['full_shorthand']) }} chord" aria-pressed="false" aria-controls="{{ $inversion['id'] }}" data-notes="{{ json_encode($inversion['chord']) }}" data-bass="{{ $request['strict'] && $request['tool'] !== 'button' ? $inversion['label']['bass'] : '' }}"><span data-chord-name class="{{ $group === 'best' ? 'h3 mb-0' : '' }}">{!! $inversion['label']['full_shorthand'] !!}</span>@icon('chevron-right', ['mr' => 0])</button>
                    @endforeach
                </div>
        </div>
        </div>
    @endif
@endforeach
</div>
