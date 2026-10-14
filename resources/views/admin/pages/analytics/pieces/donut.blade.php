@php($offset = 0)
<div class="pa-donut-layout {{ !empty($compact) ? 'pa-donut-layout--compact' : '' }}"><div class="pa-donut"><svg viewBox="0 0 120 120" role="img" aria-label="{{ $label }}. {{ $total }} pieces; exact counts and percentages follow."><circle class="pa-donut-track" cx="60" cy="60" r="48"/>

@foreach($categories as $category)
@php($size = $total ? $category['total'] / $total * 100 : 0)
@if($size)<circle cx="60" cy="60" r="48" fill="none" stroke="{{ $category['color'] }}" stroke-width="14" pathLength="100" stroke-dasharray="{{ $size }} {{ 100 - $size }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 60 60)"><title>{{ $category['label'] }}: {{ number_format($category['total']) }} pieces ({{ $category['share'] }}%)</title></circle>
@endif
@php($offset += $size)
@endforeach
    </svg><div><strong>{{ number_format($total) }}</strong><span>pieces</span></div></div>
    <ul class="pa-legend">
@foreach($categories as $category)<li><i style="background:{{ $category['color'] }}" aria-hidden="true"></i><span>{{ $category['label'] }}</span><strong>{{ number_format($category['total']) }}</strong><small>{{ $category['share'] }}%</small></li>
@endforeach</ul>
</div>
