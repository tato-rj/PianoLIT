@php
    $peak = max(1, max(array_column($trend, 'total')));
    $magnitude = pow(10, floor(log10(max(1, $peak / 4))));
    $step = (int) (ceil(max(1, $peak / 4) / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $points = [];
    foreach($trend as $index => $month) $points[] = (60 + $index * 690 / max(1, count($trend) - 1)).','.round(225 - $month['total'] / $maximum * 185, 2);
    $line = implode(' ', $points);
@endphp
<article class="pa-card pa-growth"><div class="pa-card-heading"><div><h3>Catalogue growth</h3><p>Running total of retained pieces, by date added.</p></div></div>
    @include('admin.components.filters.form', ['id' => 'catalogue-growth-filters', 'label' => 'Catalogue growth filters', 'action' => route('admin.analytics.pieces'), 'fields' => [['name' => 'months', 'label' => 'Growth range', 'options' => [12 => '12 months', 24 => '24 months', 36 => '36 months']]], 'state' => ['months' => $report['months']]])
    <div class="pa-growth-total"><strong>{{ number_format(end($trend)['total']) }}</strong><span>pieces added through {{ end($trend)['label'] }}</span></div>
    <div class="pa-line-scroll" tabindex="0" role="region" aria-label="Catalogue growth chart. Scroll to see all months."><svg class="pa-line-chart" viewBox="0 0 780 285" role="img" aria-label="Catalogue grew from {{ $trend[0]['total'] }} to {{ end($trend)['total'] }} retained pieces across {{ count($trend) }} months. Exact monthly totals are in the table below.">
        <defs><linearGradient id="pa-growth-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a692ce" stop-opacity=".35"/><stop offset="100%" stop-color="#a692ce" stop-opacity="0"/></linearGradient></defs>

@for($tick = 0; $tick <= 4; $tick++)<line class="pa-line-grid" x1="60" y1="{{ 225 - $tick * 46.25 }}" x2="750" y2="{{ 225 - $tick * 46.25 }}"/><text class="pa-line-label" x="48" y="{{ 230 - $tick * 46.25 }}" text-anchor="end">{{ number_format($tick * $step) }}</text>
@endfor
        <polygon points="60,225 {{ $line }} 750,225" fill="url(#pa-growth-fill)"/><polyline points="{{ $line }}" fill="none" stroke="#8970b5" stroke-width="3" stroke-linejoin="round"/>

@foreach($trend as $month)
@php($x = 60 + $loop->index * 690 / max(1, count($trend) - 1))<circle cx="{{ $x }}" cy="{{ 225 - $month['total'] / $maximum * 185 }}" r="4" fill="#fff" stroke="#8970b5" stroke-width="2"><title>{{ $month['label'] }}: {{ $month['total'] }} total pieces; {{ $month['added'] }} added</title></circle>
@if($loop->first || $loop->last || $loop->index % (count($trend) > 12 ? 6 : 3) === 0)<text class="pa-line-label" x="{{ $x }}" y="258" text-anchor="{{ $loop->first ? 'start' : ($loop->last ? 'end' : 'middle') }}"><tspan x="{{ $x }}">{{ $month['short'] }}</tspan><tspan x="{{ $x }}" dy="22">{{ substr($month['month'], 0, 4) }}</tspan></text>
@endif
@endforeach
    </svg></div>
    <p class="pa-note">Scroll horizontally if needed to see all months. The range changes growth only. The current month is partial; undated and future creation timestamps are omitted. Other charts show the whole current catalogue.</p>
    <details class="pa-details"><summary>Monthly additions & totals</summary><div class="pa-table-scroll"><table><caption class="visually-hidden">Retained catalogue pieces by calendar month of creation</caption><thead><tr><th>Month</th><th>Added</th><th>Running total</th></tr></thead><tbody>
@foreach($trend as $month)<tr><th scope="row">{{ $month['label'] }}</th><td>{{ number_format($month['added']) }}</td><td>{{ number_format($month['total']) }}</td></tr>
@endforeach</tbody></table></div></details>
</article>
