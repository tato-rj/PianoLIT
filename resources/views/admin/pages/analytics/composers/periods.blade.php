@php
    $step = max(1, (int) ceil(max(array_column($periods, 'total')) / 4));
    $ceiling = $step * 4;
@endphp
<article class="ca-card"><div class="ca-heading"><div><h3>Composers by period</h3><p>All composer records compared with those having at least one piece.</p></div></div>
    <div class="ca-series"><span><i style="background:#a692ce"></i>All composers</span><span><i style="background:#94c6b0"></i>With pieces</span></div>
    <div class="ca-bars-layout"><div class="ca-bar-axis" aria-hidden="true">@for($tick = 0; $tick <= 4; $tick++)<span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>@endfor</div>
        <div class="ca-bars-scroll" tabindex="0" role="region" aria-label="Composers by period. Scroll horizontally to see all periods."><div class="ca-bars" style="min-width:{{ max(560, count($periods) * 105) }}px">
            <div class="ca-bar-grid" aria-hidden="true">@for($tick = 0; $tick <= 4; $tick++)<i style="bottom:{{ $tick * 25 }}%"></i>@endfor</div>
            <ol class="ca-columns">@foreach($periods as $period)<li><div class="ca-bar-pair"><span style="height:{{ $period['total'] / $ceiling * 100 }}%;background:#a692ce"><strong>{{ number_format($period['total']) }}</strong></span><span style="height:{{ $period['represented'] / $ceiling * 100 }}%;background:#94c6b0"><strong>{{ number_format($period['represented']) }}</strong></span></div><a href="{{ route('admin.analytics.composers', ['period' => $period['key'] ?: '__missing']) }}#composer-directory">{{ $period['label'] }}</a><span class="visually-hidden">{{ $period['total'] }} composers, {{ $period['represented'] }} with pieces.</span></li>@endforeach</ol>
        </div></div>
    </div>
    <p class="ca-note">With pieces is a subset of All composers; the two counts are not added together. Periods come from composer profiles, independently of piece tags.</p>
</article>
