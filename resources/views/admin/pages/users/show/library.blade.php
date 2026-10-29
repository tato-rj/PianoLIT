<div class="up-grid up-library">
    <section class="up-card" id="up-favorites" aria-labelledby="up-favorites-title">
        <div class="up-section-title"><span class="up-icon up-icon--peach">@icon('heart', ['mr' => 0])</span><div><h2 id="up-favorites-title">Saved music <span class="up-count">{{ number_format($favorites->total()) }}</span></h2><p>Distinct saved pieces · {{ number_format($counts['folders']) }} {{ $counts['folders'] === 1 ? 'collection' : 'collections' }}.</p></div></div>
        <ul class="up-piece-list">
            @forelse($favorites as $piece)
            <li><span class="up-piece-icon">@icon('music', ['mr' => 0])</span><div><a href="{{ route('webapp.pieces.show', $piece->id) }}" target="_blank" rel="noopener noreferrer">{{ $piece->name }}</a><small>{{ $piece->composer ?: 'Composer not recorded' }}</small></div><a class="up-piece-open" href="{{ route('webapp.pieces.show', $piece->id) }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $piece->name }}">@icon('arrow-up-right', ['mr' => 0])</a></li>
            @empty<li class="up-empty">@icon('heart', ['mr' => 0])<p>No saved pieces yet.</p></li>@endforelse
        </ul>
        @include('admin.components.pagination', ['paginator' => $favorites->fragment('up-favorites'), 'label' => 'Saved music'])
        @unless($analyticsExcluded)<a class="up-text-link" href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'music']) }}">View collections & score details @icon('arrow-up-right', ['mr' => 0])</a>@endunless
    </section>
    <section class="up-card" id="up-requests" aria-labelledby="up-requests-title">
        <div class="up-section-title"><span class="up-icon up-icon--lavender">@icon('video', ['mr' => 0])</span><div><h2 id="up-requests-title">Synthesia requests <span class="up-count">{{ number_format($requests->total()) }}</span></h2><p>Current requests and publication status.</p></div></div>
        <ul class="up-piece-list">
            @forelse($requests as $synthesiaRequest)
            <li><span class="up-piece-icon">@icon('video', ['mr' => 0])</span><div>@if($synthesiaRequest->name)<a href="{{ route('webapp.pieces.show', $synthesiaRequest->piece_id) }}" target="_blank" rel="noopener noreferrer">{{ $synthesiaRequest->name }}</a>@else<strong>Piece unavailable</strong>@endif<small>{{ $synthesiaRequest->composer ?: 'Composer not recorded' }} · {{ $synthesiaRequest->created_at ? carbon($synthesiaRequest->created_at)->format('M j, Y') : 'Date not recorded' }}</small></div><span class="up-pill {{ $synthesiaRequest->published_at ? 'up-pill--green' : '' }}">{{ $synthesiaRequest->published_at ? 'Published' : 'Pending' }}</span></li>
            @empty<li class="up-empty">@icon('video', ['mr' => 0])<p>No Synthesia requests.</p></li>@endforelse
        </ul>
        @include('admin.components.pagination', ['paginator' => $requests->fragment('up-requests'), 'label' => 'Synthesia requests'])
    </section>
</div>
