<ul class="ua-piece-list">
@forelse($items as $item)
    @php($id = $item->resource_id ?? $item->piece_id)
    @php($piece = $pieces[$id] ?? null)
    <li><div>@if($piece)<a href="{{ route('admin.pieces.edit', $id) }}">{{ $piece->name }}</a><small>{{ $piece->composer }}</small>@else<strong>Unavailable piece #{{ $id }}</strong>@endif</div>
        <span>@if(isset($item->total)){{ $item->total }} opens<small>{{ carbon($item->last_opened)->format('M j, Y') }}</small>
        @elseif(isset($item->saved_at))<small>Saved state · {{ carbon($item->saved_at)->format('M j, Y') }}</small>
        @elseif(isset($item->updated_at))<small>Last modified {{ carbon($item->updated_at)->format('M j, Y') }} · revision {{ $item->revision }}</small>@endif</span>
    </li>
@empty<li class="ua-muted">No recorded pieces in this section.</li>@endforelse
</ul>
