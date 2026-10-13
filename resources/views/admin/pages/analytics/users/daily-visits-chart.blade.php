@php
    $peak = max(array_column($trend, 'total'));
    $rawStep = max(1, $peak / 4);
    $magnitude = pow(10, floor(log10($rawStep)));
    $step = (int) (ceil($rawStep / $magnitude) * $magnitude);
    $maximum = $step * 4;
    $mobileColumnWidth = max(28, strlen(number_format($peak)) * 7 + 6);
@endphp
<div class="ua-chart">
    <div class="ua-chart-unit" aria-hidden="true">Visits</div>
    <div class="ua-chart-layout">
        <div class="ua-chart-y" aria-hidden="true" style="min-width:{{ max(30, strlen(number_format($maximum)) * 7) }}px">
            @for($tick = 0; $tick <= 4; $tick++)
            <span style="bottom:{{ $tick * 25 }}%">{{ number_format($tick * $step) }}</span>
            @endfor
        </div>
        <div class="ua-chart-scroll" tabindex="0" role="region" aria-label="Daily visit counts by date. Scroll horizontally to see all dates.">
            <div class="ua-chart-canvas" style="--ua-chart-width:{{ count($trend) * max(64, strlen(number_format($peak)) * 8 + 16) }}px; --ua-chart-mobile-width:{{ count($trend) * $mobileColumnWidth }}px">
                <div class="ua-chart-grid" aria-hidden="true">
                    @for($tick = 0; $tick <= 4; $tick++)
                    <span style="bottom:{{ $tick * 25 }}%"></span>
                    @endfor
                </div>
                <ol class="ua-chart-bars" role="list">
                    @foreach($trend as $day)
                    <li class="ua-chart-column" aria-label="{{ $day['date'] }}: {{ number_format($day['total']) }} {{ $day['total'] === 1 ? 'visit' : 'visits' }}">
                        <div class="ua-chart-track" aria-hidden="true">
                            <div class="ua-chart-bar" style="height:{{ $day['total'] / $maximum * 100 }}%">
                                <span class="ua-chart-count">{{ number_format($day['total']) }}</span>
                            </div>
                        </div>
                        <span class="ua-chart-date" aria-hidden="true"><span>{{ explode(' ', $day['date'])[0] }}</span> <span>{{ explode(' ', $day['date'])[1] }}</span></span>
                    </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="ua-chart-x" aria-hidden="true">Date</div>
    @if(count($trend) > 7 || $mobileColumnWidth > 28)
    <p class="ua-chart-hint {{ count($trend) > 7 ? '' : 'ua-chart-hint-mobile' }}">Swipe or scroll horizontally to see all dates.</p>
    @endif
</div>
