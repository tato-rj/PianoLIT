@php
    $step = max(1, (int) ceil(max(array_column($growth, 'total')) / 4));
    $ceiling = $step * 4;
    $points = [];
    foreach($growth as $index => $month) $points[] = (50 + $index * 660 / 11).','.round(200 - $month['total'] / $ceiling * 160, 2);
    $line = implode(' ', $points);
@endphp
<article class="ca-card ca-growth"><div class="ca-heading"><div><h2>Composers added by month</h2><p>Last 12 calendar months · current month to date.</p></div><strong class="ca-chart-total">{{ number_format(array_sum(array_column($growth, 'total'))) }}<small>added</small></strong></div>
    <div class="ca-line-scroll" tabindex="0" role="region" aria-label="Monthly composer additions. Scroll horizontally to see all months."><svg class="ca-line-chart" viewBox="0 0 750 265" role="img" aria-label="Composers added by month. Exact monthly counts appear in the table below.">
        <defs><linearGradient id="ca-growth-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a692ce" stop-opacity=".32"/><stop offset="100%" stop-color="#a692ce" stop-opacity="0"/></linearGradient></defs>
        @for($tick = 0; $tick <= 4; $tick++)<line class="ca-line-grid" x1="50" y1="{{ 200 - $tick * 40 }}" x2="710" y2="{{ 200 - $tick * 40 }}"/><text class="ca-line-label" x="40" y="{{ 205 - $tick * 40 }}" text-anchor="end">{{ number_format($tick * $step) }}</text>@endfor
        <polygon points="50,200 {{ $line }} 710,200" fill="url(#ca-growth-fill)"/><polyline points="{{ $line }}" fill="none" stroke="#8970b5" stroke-width="2.5" stroke-linejoin="round"/>
        @foreach($growth as $month)
        @php($x = 50 + $loop->index * 660 / 11)
        <circle cx="{{ $x }}" cy="{{ 200 - $month['total'] / $ceiling * 160 }}" r="4" fill="#fff" stroke="#8970b5" stroke-width="2"><title>{{ $month['label'] }}: {{ $month['total'] }} composers added</title></circle>
        @if($loop->first || $loop->last || $loop->index % 3 === 0)<text class="ca-line-label" x="{{ $x }}" y="230" text-anchor="{{ $loop->first ? 'start' : ($loop->last ? 'end' : 'middle') }}"><tspan x="{{ $x }}">{{ $month['short'] }}</tspan><tspan x="{{ $x }}" dy="21">{{ substr($month['month'], 0, 4) }}</tspan></text>@endif
        @endforeach
    </svg></div>
    <p class="ca-note">Counts composer records that still exist by creation date. Deleted records, unknown and future creation dates are omitted.</p>
    <details class="ca-details"><summary>Monthly counts</summary><div class="ca-table-scroll"><table><caption class="visually-hidden">Retained composers added by calendar month</caption><thead><tr><th scope="col">Month</th><th scope="col">Composers added</th></tr></thead><tbody>@foreach($growth as $month)<tr><th scope="row">{{ $month['label'] }}</th><td>{{ number_format($month['total']) }}</td></tr>@endforeach</tbody></table></div></details>
</article>
