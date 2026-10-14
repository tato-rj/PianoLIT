<article class="pa-card"><div class="pa-card-heading"><div><h3>{{ $title }}</h3><p>{{ ucfirst($unit) }} · all time</p></div></div>

@php($peak = max(1, count($rows) ? max(array_column($rows, 'total')) : 1))
    <ol class="pa-ranking" style="--pa-color:{{ $color }}">
@forelse($rows as $row)<li><span class="pa-rank-number">{{ $loop->iteration }}</span><div><a href="{{ route('admin.pieces.edit', $row['id']) }}">{{ $row['label'] }}</a><span class="pa-rank-composer">{{ $row['composer'] }}</span><div class="pa-horizontal-track" aria-hidden="true"><i style="width:{{ $row['total'] / $peak * 100 }}%"></i></div></div><strong>{{ number_format($row['total']) }}<span>{{ $unit }}</span></strong></li>
@empty<li class="pa-empty">No {{ $unit }} have been recorded for current pieces.</li>
@endforelse</ol>
</article>
