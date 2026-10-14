@php($peak = max(1, count($rows) ? max(array_column($rows, 'total')) : 1))
<ol class="pa-horizontal-bars" style="--pa-color:{{ $color }}">
@forelse($rows as $row)<li><div>
@if(!empty($links))<a href="{{ route($links, $row['id']) }}">{{ $row['label'] }}</a>
@else<span>{{ $row['label'] }}</span>
@endif<strong>{{ number_format($row['total']) }} <small>{{ $row['share'] }}%</small></strong></div><div class="pa-horizontal-track" aria-hidden="true"><i style="width:{{ $row['total'] / $peak * 100 }}%"></i></div></li>
@empty<li class="pa-empty">{{ $empty ?? 'No pieces to show.' }}</li>
@endforelse</ol>
