@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid folder-analytics">
    @include('admin.components.page.title', ['title' => 'Favorite folders', 'subtitle' => 'How users organize their repertoire, and what they choose to save.'])
    <div class="fa-context"><span>Current custom folders · all time</span><a href="#folder-directory">Explore folders @icon('arrow-down', ['mr' => 0])</a></div>

    <div class="fa-metrics">
        <article class="fa-metric fa-metric--mint"><span>@icon('folder', ['mr' => 0]) Custom folders</span><strong>{{ number_format($summary['total']) }}</strong><p>{{ number_format($summary['total'] - $summary['filled']) }} empty · {{ number_format($summary['filled']) }} with pieces</p></article>
        <article class="fa-metric fa-metric--lavender"><span>@icon('users', ['mr' => 0]) Users with folders</span><strong>{{ number_format($summary['owners']) }}</strong><p>{{ $summary['adoption'] }}% of {{ number_format($summary['eligible_users']) }} included accounts</p></article>
        <article class="fa-metric fa-metric--peach"><span>@icon('music', ['mr' => 0]) Pieces in folders</span><strong>{{ number_format($summary['placements']) }}</strong><p>A piece in two folders counts twice.</p></article>
        <article class="fa-metric fa-metric--gold"><span>@icon('layers', ['mr' => 0]) Average folder size</span><strong>{{ $summary['average'] }}</strong><p>Pieces per folder · includes empty folders</p></article>
    </div>

    <div class="fa-overview">
        @include('admin.pages.analytics.favorites.growth')
        <article class="fa-card"><div class="fa-heading"><div><h2>How full are the folders?</h2><p>Click a size to explore those folders.</p></div></div>
            <div class="fa-filled"><svg viewBox="0 0 100 100" role="img" aria-label="{{ $summary['filled'] }} of {{ $summary['total'] }} folders contain pieces"><circle cx="50" cy="50" r="40" class="fa-ring-track"/><circle cx="50" cy="50" r="40" class="fa-ring-fill" pathLength="100" stroke-dasharray="{{ $summary['filled_share'] }} 100" opacity="{{ $summary['filled'] ? 1 : 0 }}"/></svg><div><strong>{{ $summary['filled_share'] }}%</strong><span>contain at least one piece</span><small>{{ number_format($summary['filled']) }} of {{ number_format($summary['total']) }} folders</small></div></div>
            <div class="fa-sizes">
                @foreach($sizes as $size)<a href="{{ route('admin.analytics.favorites', array_merge(array_diff_key($filters, array_flip(['page', 'folder', 'pieces_page'])), ['size' => $size['key']])) }}#folder-directory" class="{{ ($filters['size'] ?? '') === $size['key'] ? 'is-selected' : '' }}"><span>{{ $size['label'] }}</span><div class="fa-track" aria-hidden="true"><i style="width:{{ $size['share'] }}%"></i></div><strong>{{ number_format($size['total']) }}</strong><small>{{ $size['share'] }}%</small></a>@endforeach
            </div>
        </article>
    </div>

    <div class="fa-rankings">
        <article class="fa-card"><div class="fa-heading"><div><h2>Most saved in folders</h2><p>Top five pieces · current custom folders.</p></div></div><ol class="fa-ranking">
            @forelse($popular as $piece)<li><span class="fa-rank">{{ $loop->iteration }}</span><div><a href="{{ route('webapp.pieces.show', $piece->id) }}" target="_blank" rel="noopener noreferrer">{{ $piece->name }}</a><small>{{ $piece->composer ?: 'Composer unavailable' }}</small></div><strong>{{ number_format($piece->total) }} <small>{{ $piece->total == 1 ? 'folder' : 'folders' }} · {{ number_format($piece->owners) }} {{ $piece->owners == 1 ? 'user' : 'users' }}</small></strong></li>@empty<li class="fa-empty">No pieces have been saved in custom folders yet.</li>@endforelse
        </ol></article>
        <article class="fa-card"><div class="fa-heading"><div><h2>Users with the most folders</h2><p>Top five accounts · includes empty folders.</p></div></div><ol class="fa-ranking">
            @forelse($owners as $owner)<li><span class="fa-rank">{{ $loop->iteration }}</span><div><a href="{{ route('admin.users.show', $owner->user_id) }}">{{ trim($owner->first_name.' '.$owner->last_name) ?: 'Unnamed account' }}</a><small>{{ number_format($owner->pieces_count) }} pieces across folders</small></div><strong>{{ number_format($owner->total) }}<small>{{ $owner->total == 1 ? 'folder' : 'folders' }}</small></strong></li>@empty<li class="fa-empty">No users have created custom folders yet.</li>@endforelse
        </ol></article>
    </div>
    <p class="fa-note fa-scope">Automatic default folders, team accounts and analytics opt-outs are excluded. This is a snapshot of saved folders, rather than a history of save or delete actions.</p>

    <section id="folder-directory" class="fa-directory" aria-labelledby="fa-directory-title">
        <div class="fa-heading"><div><h2 id="fa-directory-title">Explore folders</h2><p>Find a collection, see its pieces, or open the owner’s account.</p></div><span class="fa-results">{{ number_format($folders->total()) }} {{ $folders->total() == 1 ? 'folder' : 'folders' }}{{ isset($filters['search']) || !empty($filters['size']) ? ' match' : ' total' }}</span></div>
        <form class="fa-filters" method="GET" action="{{ route('admin.analytics.favorites') }}#folder-directory">
            <div class="fa-search"><label for="fa-search">Search folders or users</label><div>@icon('search', ['mr' => 0])<input id="fa-search" name="search" type="search" maxlength="160" value="{{ $filters['search'] ?? '' }}" placeholder="Folder, name or email"></div></div>
            <div><label for="fa-size">Folder size</label><select id="fa-size" name="size"><option value="">All sizes</option>@foreach(\App\Analytics\FolderReport::SIZES as $key => $label)<option value="{{ $key }}" {{ ($filters['size'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div><label for="fa-sort">Sort by</label><select id="fa-sort" name="sort">@foreach(['newest' => 'Newest folders', 'oldest' => 'Oldest folders', 'largest' => 'Most pieces', 'saved' => 'Latest saved piece', 'name' => 'Folder name', 'owner' => 'User name'] as $key => $label)<option value="{{ $key }}" {{ ($filters['sort'] ?? 'newest') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <button type="submit" class="fa-button fa-button--dark">Apply</button>@if(isset($filters['search']) || !empty($filters['size']) || isset($filters['sort']))<a class="fa-reset" href="{{ route('admin.analytics.favorites') }}#folder-directory">Reset</a>@endif
        </form>

        @if($selected) @include('admin.pages.analytics.favorites.detail') @endif

        <div class="fa-folder-grid">
            @forelse($folders as $folder)
            <article class="fa-folder {{ $selected && $selected->id == $folder->id ? 'is-selected' : '' }}">
                <div class="fa-folder-top"><span class="fa-folder-icon">@icon('folder', ['mr' => 0])</span><span class="fa-badge {{ $folder->pieces_count ? '' : 'fa-badge--empty' }}">{{ $folder->pieces_count ? $folder->pieces_count.' '.($folder->pieces_count == 1 ? 'piece' : 'pieces') : 'Empty folder' }}</span></div>
                <h3><a href="{{ route('admin.analytics.favorites', array_merge($filters, ['folder' => $folder->id, 'pieces_page' => null])) }}#folder-detail">{{ $folder->name }}</a></h3>
                <a class="fa-owner" href="{{ route('admin.users.show', $folder->user_id) }}">@icon('user-round', ['mr' => 0]) {{ trim($folder->first_name.' '.$folder->last_name) ?: 'Unnamed account' }}</a>
                @if($folder->description)<p class="fa-description">{{ $folder->description }}</p>@endif
                <dl><div><dt>Composers</dt><dd>{{ number_format($folder->composers_count) }}</dd></div><div><dt>Created</dt><dd>{{ $folder->created_at ? carbon($folder->created_at)->format('M j, Y') : 'Date unknown' }}</dd></div><div><dt>Latest saved piece</dt><dd>{{ $folder->last_saved_at ? carbon($folder->last_saved_at)->format('M j, Y') : 'No saved pieces' }}</dd></div></dl>
                <a class="fa-folder-action" href="{{ route('admin.analytics.favorites', array_merge($filters, ['folder' => $folder->id, 'pieces_page' => null])) }}#folder-detail">{{ $folder->pieces_count ? 'View pieces' : 'View folder' }} @icon('arrow-up-right', ['mr' => 0])</a>
            </article>
            @empty<div class="fa-directory-empty"><span class="fa-folder-icon">@icon('folder-search', ['mr' => 0])</span><h3>{{ $folders->total() ? 'No folders on this page' : ($summary['total'] ? 'No folders match these filters' : 'No custom folders yet') }}</h3><p>{{ $folders->total() ? 'Return to the first page of these results.' : ($summary['total'] ? 'Try a different search or folder size.' : 'Folders will appear here when users create their own collections.') }}</p>@if($folders->total())<a class="fa-button" href="{{ route('admin.analytics.favorites', array_diff_key($filters, array_flip(['page', 'folder', 'pieces_page']))) }}#folder-directory">First page</a>@elseif($summary['total'])<a class="fa-button" href="{{ route('admin.analytics.favorites') }}#folder-directory">Show all folders</a>@endif</div>@endforelse
        </div>
        @if($folders->total())<div class="fa-pagination"><p>Showing {{ $folders->firstItem() ?? 0 }}–{{ $folders->lastItem() ?? 0 }} of {{ number_format($folders->total()) }} folders</p>{{ $folders->fragment('folder-directory')->links() }}</div>@endif
    </section>
</div></div>
@endsection
