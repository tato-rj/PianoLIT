<div class="ua-playback mt-0"><div class="ua-action-line"><strong>{{ $playback['label'] }}</strong><time>{{ $playback['started']->format('g:i:s A') }}</time></div>
    @php($playbackPieceId = $playback['event']->resource_type === 'piece' ? $playback['event']->resource_id : (\App\Activity\Presentation::metadata($playback['event'])['piece_id'] ?? null))
    @if($playbackPieceId && isset($pieces[$playbackPieceId]) && (!$group['piece'] || $group['piece']->id != $playbackPieceId))<a href="{{ route('webapp.pieces.show', $playbackPieceId) }}" target="_blank" rel="noopener noreferrer">{{ $pieces[$playbackPieceId]->name }}</a>@endif
    <p>{{ isset($playback['watched']) && $playback['watched'] > 0 && $playback['watched'] < 1 ? 'Less than 1s' : \App\Activity\Time::duration(isset($playback['watched']) ? $playback['watched'] * 1000 : null) }} playing
        @if($playback['coverage'] !== null) · {{ round($playback['coverage']) }}% unique content played @else · Unique watch coverage unavailable @endif
    </p>
    @if($playback['coverage'] !== null)<meter min="0" max="100" value="{{ min(100, max(0, $playback['coverage'])) }}" aria-label="Unique content played">{{ round($playback['coverage']) }}%</meter>@endif
</div>
