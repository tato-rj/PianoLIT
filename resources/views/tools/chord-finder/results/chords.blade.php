@foreach(['best' => 'Best matches', 'other' => 'Other possibilities'] as $group => $heading)
    @php
        $matches = collect($request['chords'])->flatMap(function ($chord) { return $chord['inversions']; })->filter(function ($inversion) use ($request, $group) {
            return $group === 'best' ? $inversion['ranking'] === $request['most_relevant'] : $inversion['ranking'] < $request['most_relevant'];
        })->sortByDesc('ranking');
    @endphp
    @if($matches->isNotEmpty())
        <div class="mb-4">
            <h3 class="h6 mb-3">{{ $heading }}</h3>
            <div class="chords-results d-flex flex-wrap gap-2">
                @foreach($matches as $inversion)
                    <button type="button" class="btn btn-secondary" href="#{{ $inversion['id'] }}" aria-pressed="false" aria-controls="{{ $inversion['id'] }}" data-notes="{{ json_encode($inversion['chord']) }}" data-bass="{{ $request['strict'] && $request['tool'] !== 'button' ? $inversion['label']['bass'] : '' }}">@icon('play'){!! $inversion['label']['full_shorthand'] !!}</button>
                @endforeach
            </div>
        </div>
    @endif
@endforeach
