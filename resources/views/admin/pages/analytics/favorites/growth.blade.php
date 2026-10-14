@php
    $peak = max(1, max(array_column($growth, 'total')));
    $step = max(1, (int) ceil($peak / 4));
    $ceiling = $step * 4;
    $recentTotal = array_sum(array_column($growth, 'total'));
@endphp
<article class="fa-card fa-growth"><div class="fa-heading"><div><h2>New folders by month</h2><p>Last 12 calendar months · current month to date.</p></div><strong class="fa-chart-total">{{ number_format($recentTotal) }}<small>created</small></strong></div>
    <div class="fa-chart-layout"><div class="fa-chart-axis" aria-hidden="true">@for($tick = 0; $tick <= 4; $tick++)<span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>@endfor</div>
        <div class="fa-chart-scroll" tabindex="0" role="region" aria-label="New folders by month. Scroll horizontally to see all months."><div class="fa-chart">
            <div class="fa-chart-grid" aria-hidden="true">@for($tick = 0; $tick <= 4; $tick++)<i style="bottom:{{ $tick * 25 }}%"></i>@endfor</div>
            <ol class="fa-columns">@foreach($growth as $month)<li><div class="fa-column-track"><span class="fa-column {{ $loop->last ? 'is-current' : '' }}" style="height:{{ $month['total'] / $ceiling * 100 }}%"><strong>{{ number_format($month['total']) }}</strong></span></div><span class="fa-month">{{ $month['short'] }}<small>{{ substr($month['month'], 0, 4) }}</small></span><span class="visually-hidden">{{ $month['label'] }}: {{ $month['total'] }} new folders</span></li>@endforeach</ol>
        </div></div>
    </div>
    <p class="fa-note">Counts folders that still exist, by creation date. Deleted folders are not reconstructed; unknown and future dates are omitted.</p>
</article>
