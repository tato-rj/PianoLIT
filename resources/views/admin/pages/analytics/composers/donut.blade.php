@php($offset = 0)
<div class="ca-donut-layout {{ !empty($compact) ? 'ca-donut-layout--compact' : '' }}">
    <div class="ca-donut"><svg viewBox="0 0 120 120" role="img" aria-label="{{ $label }}. {{ $summary['total'] }} composers; exact counts follow."><circle class="ca-donut-track" cx="60" cy="60" r="48"/>
        @foreach($categories as $category)
        @php($size = $summary['total'] ? $category['total'] / $summary['total'] * 100 : 0)
        @if($size)<circle cx="60" cy="60" r="48" fill="none" stroke="{{ $category['color'] }}" stroke-width="14" pathLength="100" stroke-dasharray="{{ $size }} {{ 100 - $size }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 60 60)"><title>{{ $category['label'] }}: {{ $category['total'] }} composers ({{ round($size, 1) }}%)</title></circle>@endif
        @php($offset += $size)
        @endforeach
    </svg><div><strong>{{ number_format($summary['total']) }}</strong><span>composers</span></div></div>
    <ul class="ca-legend">
        @forelse($categories as $category)<li><i style="background:{{ $category['color'] }}" aria-hidden="true"></i><span>@if($links)<a href="{{ route('admin.analytics.composers', ['depth' => $category['key']]) }}#composer-directory">{{ $category['label'] }}</a>@else{{ $category['label'] }}@endif</span><strong>{{ number_format($category['total']) }}</strong><small>{{ $summary['total'] ? round($category['total'] / $summary['total'] * 100, 1) : 0 }}%</small></li>@empty<li class="ca-empty">No composer profiles yet.</li>@endforelse
    </ul>
</div>
