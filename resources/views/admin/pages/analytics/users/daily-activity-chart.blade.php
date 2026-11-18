@php
    $series = $series ?? ['total' => ['Visits', 'visits'], 'pieces' => ['Pieces opened', 'pieces'], 'media_starts' => ['Media starts', 'media']];
    $peak = 0;
    foreach ($series as $key => $definition) $peak = max($peak, max(array_column($trend, $key) ?: [0]));
    $seriesDescription = implode(', ', array_column($series, 0));
    $rawStep = max(1, $peak / 4);
    $magnitude = pow(10, floor(log10($rawStep)));
    $step = (int) (ceil($rawStep / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $dayCount = count($trend);
    $visibleDays = 10;
    $canvasWidth = max(100, 100 * $dayCount / $visibleDays);
    $seriesWidth = max(22, strlen(number_format($peak)) * 9 + 4);
    $dayWidth = max(64, $seriesWidth * count($series) + 8);
@endphp
<div class="ua-chart">
    <div class="ua-chart-unit" aria-hidden="true">Count</div>
    <div class="ua-chart-layout">
        <div class="ua-chart-y" aria-hidden="true" style="min-width:{{ max(36, strlen(number_format($maximum)) * 9) }}px">
            @for($tick = 0; $tick <= 4; $tick++)
            <span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>
            @endfor
        </div>
        <div class="ua-chart-scroll" data-daily-activity-scroll tabindex="0" role="region" aria-label="Daily activity by date: {{ $seriesDescription }}. Scroll horizontally if needed to see all dates.">
            <div class="ua-chart-canvas" style="--ua-chart-width:{{ $canvasWidth }}%; --ua-chart-min-width:{{ $dayCount * $dayWidth }}px">
                <div class="ua-chart-grid" aria-hidden="true">
                    @for($tick = 0; $tick <= 4; $tick++)
                    <span style="bottom:{{ $tick * 25 }}%"></span>
                    @endfor
                </div>
                <ol class="ua-chart-bars" role="list">
                    @foreach($trend as $day)
                    @php($dayDescriptions = [])
                    @foreach($series as $key => [$label, $tone])@php($dayDescriptions[] = $label.': '.number_format($day[$key]))@endforeach
                    <li class="ua-chart-column" aria-label="{{ $day['weekday'] }}, {{ $day['day'] }} · {{ implode(', ', $dayDescriptions) }}">
                        <div class="ua-chart-group" aria-hidden="true">
                            @foreach($series as $key => [$label, $tone])
                            <div class="ua-chart-track" title="{{ $day['weekday'] }}, {{ $day['day'] }} · {{ $label }}: {{ number_format($day[$key]) }}">
                                <div class="ua-chart-bar ua-chart-series--{{ $tone }}" style="height:{{ $day[$key] / $maximum * 100 }}%">
                                    <span class="ua-chart-count">{{ number_format($day[$key]) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <span class="ua-chart-date" aria-hidden="true">{{ $day['weekday'] }}<br>{{ $day['date'] }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="ua-chart-x" aria-hidden="true">Date</div>
    <p class="ua-chart-hint">Swipe or scroll horizontally if needed to see all dates.</p>
</div>
