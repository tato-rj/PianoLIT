@php
    $peak = max(array_column($trend, 'total'));
    $rawStep = max(1, $peak / 4);
    $magnitude = pow(10, floor(log10($rawStep)));
    $step = (int) (ceil($rawStep / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $mobileColumnWidth = max(36, strlen(number_format($peak)) * 10 + 4);
@endphp
<div class="admin-chart">
    <div class="admin-chart-unit" aria-hidden="true">Recorded events</div>
    <div class="admin-chart-layout">
        <div class="admin-chart-y" aria-hidden="true" style="min-width:{{ max(36, strlen(number_format($maximum)) * 9) }}px">
            @for($tick = 0; $tick <= 4; $tick++)
            <span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>
            @endfor
        </div>
        <div class="admin-chart-scroll" tabindex="0" role="region" aria-label="Daily recorded events by date. Scroll horizontally to see all dates.">
            <div class="admin-chart-canvas" style="--admin-chart-width:{{ count($trend) * max(64, strlen(number_format($peak)) * 10 + 20) }}px; --admin-chart-mobile-width:{{ count($trend) * $mobileColumnWidth }}px">
                <div class="admin-chart-grid" aria-hidden="true">
                    @for($tick = 0; $tick <= 4; $tick++)
                    <span style="bottom:{{ $tick * 25 }}%"></span>
                    @endfor
                </div>
                <ol class="admin-chart-bars" role="list">
                    @foreach($trend as $day)
                    <li class="admin-chart-column" aria-label="{{ $day['label'] }}: {{ number_format($day['total']) }} {{ $day['total'] === 1 ? 'recorded event' : 'recorded events' }}">
                        <div class="admin-chart-track" aria-hidden="true">
                            <span class="admin-chart-bar" style="--bar-height:{{ $day['total'] / $maximum * 100 }}%; --bar-hue:{{ round(8 + 100 * $loop->index / max(1, $loop->count - 1)) }}">
                                <span class="admin-chart-count">{{ number_format($day['total']) }}</span>
                            </span>
                        </div>
                        <span class="admin-chart-date" aria-hidden="true"><span>{{ explode(' ', $day['date'])[0] }}</span> <span>{{ explode(' ', $day['date'])[1] }}</span></span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <p class="admin-chart-hint">Scroll horizontally if needed to see all dates.</p>
</div>
