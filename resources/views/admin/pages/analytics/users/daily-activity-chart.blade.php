@php
    $series = ['total' => ['Visits', 'visits'], 'pieces' => ['Pieces opened', 'pieces'], 'media_starts' => ['Media starts', 'media']];
    $peak = max(array_merge(array_column($trend, 'total'), array_column($trend, 'pieces'), array_column($trend, 'media_starts')));
    $rawStep = max(1, $peak / 4);
    $magnitude = pow(10, floor(log10($rawStep)));
    $step = (int) (ceil($rawStep / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $seriesWidth = max(28, strlen(number_format($peak)) * 10 + 8);
    $groupWidth = $seriesWidth * 3 + 16;
@endphp
<div class="ua-chart">
    <div class="ua-chart-unit" aria-hidden="true">Count</div>
    <div class="ua-chart-layout">
        <div class="ua-chart-y" aria-hidden="true" style="min-width:{{ max(36, strlen(number_format($maximum)) * 9) }}px">
            @for($tick = 0; $tick <= 4; $tick++)
            <span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>
            @endfor
        </div>
        <div class="ua-chart-scroll" tabindex="0" role="region" aria-label="Daily visits, pieces opened and media starts by date. Scroll horizontally to see all dates.">
            <div class="ua-chart-canvas" style="--ua-chart-width:{{ count($trend) * ($groupWidth + 16) - 16 }}px; --ua-chart-series-width:{{ $seriesWidth }}px">
                <div class="ua-chart-grid" aria-hidden="true">
                    @for($tick = 0; $tick <= 4; $tick++)
                    <span style="bottom:{{ $tick * 25 }}%"></span>
                    @endfor
                </div>
                <ol class="ua-chart-bars" role="list">
                    @foreach($trend as $day)
                    <li class="ua-chart-column" aria-label="{{ $day['day'] }} · Visits: {{ number_format($day['total']) }}, Pieces opened: {{ number_format($day['pieces']) }}, Media starts: {{ number_format($day['media_starts']) }}">
                        <div class="ua-chart-group" aria-hidden="true">
                            @foreach($series as $key => [$label, $tone])
                            <div class="ua-chart-track" title="{{ $day['day'] }} · {{ $label }}: {{ number_format($day[$key]) }}">
                                <div class="ua-chart-bar ua-chart-series--{{ $tone }}" style="height:{{ $day[$key] / $maximum * 100 }}%">
                                    <span class="ua-chart-count">{{ number_format($day[$key]) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <span class="ua-chart-date" aria-hidden="true">{{ $day['date'] }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="ua-chart-x" aria-hidden="true">Date</div>
    <p class="ua-chart-hint">Swipe or scroll horizontally to see all dates.</p>
</div>
