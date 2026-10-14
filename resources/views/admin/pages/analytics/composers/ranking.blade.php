<article class="ca-card"><div class="ca-heading"><div><h3>{{ $title }}</h3><p>{{ $metric === 'saves' ? 'Current saves on existing pieces' : 'All-time recorded piece views' }} · top six.</p></div></div>
    <ol class="ca-ranking" style="--ca-color:{{ $color }}">
        @forelse($rows as $composer)
        <li><div class="ca-ranking-label"><a href="{{ route('admin.composers.edit', $composer['id']) }}">{{ $composer['name'] }}</a><strong>{{ number_format($composer[$metric]) }} <small>{{ $unit }}</small></strong></div><div class="ca-track" aria-hidden="true"><i style="width:{{ $composer[$metric] / max(1, max(array_column($rows, $metric))) * 100 }}%"></i></div><p>{{ number_format($composer['pieces']) }} {{ $composer['pieces'] === 1 ? 'piece' : 'pieces' }} @if($metric === 'views')· {{ round($composer['views'] / max(1, $composer['pieces']), 1) }} views / piece @else· {{ number_format($composer['savers']) }} {{ $composer['savers'] === 1 ? 'account' : 'accounts' }} · {{ $composer['saves_per_piece'] }} saves / piece @endif</p></li>
        @empty<li class="ca-empty">{{ $empty }}</li>@endforelse
    </ol>
</article>
