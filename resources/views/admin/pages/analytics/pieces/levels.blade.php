@php
    $peak = max(1, max(array_column($levels, 'total')));
    $magnitude = pow(10, floor(log10(max(1, $peak / 4))));
    $step = (int) (ceil(max(1, $peak / 4) / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $barWidth = max(28, strlen(number_format($peak)) * 10 + 4);
@endphp
<article class="pa-card"><div class="pa-card-heading"><div><h3>Pieces by level</h3><p>Main level tags · compare catalogue size with media coverage.</p></div></div>
    <div class="pa-bar-legend"><span><i style="background:#a692ce"></i>All pieces</span><span><i style="background:#94c6b0"></i>With all three media</span></div>
    <div class="pa-columns-layout"><div class="pa-columns-axis" aria-hidden="true">
@for($tick = 0; $tick <= 4; $tick++)<span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>
@endfor</div>
        <div class="pa-columns-scroll" tabindex="0" role="region" aria-label="Pieces by level, with score, full audio and video coverage. Scroll to see all levels."><div class="pa-columns-chart" style="min-width:{{ count($levels) * max(115, $barWidth * 2 + 36) }}px"><div class="pa-column-grid" aria-hidden="true">
@for($tick = 0; $tick <= 4; $tick++)<i style="bottom:{{ $tick * 25 }}%"></i>
@endfor</div>
            <ol class="pa-columns">
@foreach($levels as $level)<li><div class="pa-column-pair"><div class="pa-column-track" style="width:{{ $barWidth }}px"><span class="pa-column-fill" style="height:{{ $level['total'] / $maximum * 100 }}%; background:#a692ce"><strong>{{ number_format($level['total']) }}</strong></span></div><div class="pa-column-track" style="width:{{ $barWidth }}px"><span class="pa-column-fill" style="height:{{ $level['complete'] / $maximum * 100 }}%; background:#94c6b0"><strong>{{ number_format($level['complete']) }}</strong></span></div></div><span class="pa-column-name">{{ $level['label'] }}</span></li>
@endforeach</ol>
        </div></div>
    </div>
    <p class="pa-note">Scroll horizontally if needed to see all levels. Media coverage is a subset of All pieces, not an additional group. Early and late sublevels are included in their main level.</p>
</article>
