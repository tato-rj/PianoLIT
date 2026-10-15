@php
    $step = max(1, (int) ceil(max(array_column($growth, 'total')) / 4));
    $ceiling = $step * 4;
    $points = [];
    foreach($growth as $index => $month) $points[] = (27.5 + $index * 55).','.round(180 - $month['total'] / $ceiling * 160, 2);
    $line = implode(' ', $points);
@endphp
<article class="ca-card ca-growth"><div class="ca-heading"><div><h2>Composers added by month</h2><p>Last 12 calendar months · current month to date.</p></div><strong class="ca-chart-total">{{ number_format(array_sum(array_column($growth, 'total'))) }}<small>added</small></strong></div>
    <div class="ca-trend-layout"><div class="ca-trend-axis" aria-hidden="true">@for($tick = 0; $tick <= 4; $tick++)<span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>@endfor</div>
        <div class="ca-line-scroll" tabindex="0" role="region" aria-label="Monthly composer additions. Scroll horizontally to see all months."><div class="ca-trend-plot">
            <svg class="ca-line-chart" viewBox="0 0 660 180" preserveAspectRatio="none" role="img" aria-label="Composers added by month. Exact monthly counts appear in the table below.">
                <defs><linearGradient id="ca-growth-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a692ce" stop-opacity=".32"/><stop offset="100%" stop-color="#a692ce" stop-opacity="0"/></linearGradient></defs>
                @for($tick = 0; $tick <= 4; $tick++)<line class="ca-line-grid" x1="0" y1="{{ 180 - $tick * 40 }}" x2="660" y2="{{ 180 - $tick * 40 }}"/>@endfor
                <polygon points="27.5,180 {{ $line }} 632.5,180" fill="url(#ca-growth-fill)"/><polyline points="{{ $line }}" fill="none" stroke="#8970b5" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                @foreach($growth as $month)<circle cx="{{ 27.5 + $loop->index * 55 }}" cy="{{ 180 - $month['total'] / $ceiling * 160 }}" r="4" fill="#fff" stroke="#8970b5" stroke-width="2" vector-effect="non-scaling-stroke"><title>{{ $month['label'] }}: {{ $month['total'] }} composers added</title></circle>@endforeach
            </svg>
            <div class="ca-trend-labels" aria-hidden="true">@foreach($growth as $month)<span>@if($loop->first || $loop->last || $loop->index % 3 === 0){{ $month['short'] }}<small>{{ substr($month['month'], 0, 4) }}</small>@endif</span>@endforeach</div>
        </div></div>
    </div>
    <p class="ca-note">Counts composer records that still exist by creation date. Deleted records, unknown and future creation dates are omitted. Scroll horizontally on smaller screens.</p>
    <details class="ca-details"><summary>Monthly counts</summary><div class="ca-table-scroll"><table><caption class="visually-hidden">Retained composers added by calendar month</caption><thead><tr><th scope="col">Month</th><th scope="col">Composers added</th></tr></thead><tbody>@foreach($growth as $month)<tr><th scope="row">{{ $month['label'] }}</th><td>{{ number_format($month['total']) }}</td></tr>@endforeach</tbody></table></div></details>
</article>
