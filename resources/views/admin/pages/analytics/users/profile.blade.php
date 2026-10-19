@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => trim($user->full_name), 'subtitle' => 'Account details, visits and music activity.'])
    <nav class="ua-breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('admin.analytics.users') }}">User analytics</a><span>/</span><a href="{{ route('admin.analytics.users.people') }}">People</a><span>/</span><span aria-current="page">{{ $user->full_name }}</span></nav>
    @php($source = optional($user->membership)->source)
    @php($status = $user->super_user ? 'Super user' : (!$source ? 'No membership' : ($user->hasActiveSubscription() ? ($source->isOnGracePeriod() ? 'Grace period' : ($source->isOnTrial() ? 'Trial' : 'Active')) : 'Expired or inactive')))
    <section class="ua-card ua-identity"><span class="ua-avatar ua-avatar-lg" aria-hidden="true">{{ mb_substr($user->first_name, 0, 1) }}</span><div><h2>{{ $user->full_name }}</h2><p>{{ $user->email }}</p><p class="ua-meta">Joined {{ $user->created_at->format('M j, Y') }} · {{ $user->origin ?: 'Signup platform not recorded' }} @if($user->location) · {{ implode(', ', array_filter([$user->location->cityName, $user->location->countryName])) }} @endif</p></div><span class="ua-badge">{{ $status }}</span></section>
    <nav class="ua-tabs" aria-label="User profile">@foreach(['overview' => 'Overview', 'visits' => 'Visits', 'music' => 'Music', 'account' => 'Account'] as $key => $label)<a href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach</nav>
    @if($tab === 'overview')
    <div class="ua-metrics ua-metrics-four">
        @foreach(['Visits' => $stats->visits, 'Pieces opened' => $stats->pieces, 'Saved favorites' => $stats->favorites, 'Scores with markings' => $stats->scores] as $label => $value)<article class="ua-card ua-metric"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></article>@endforeach
    </div>
    <div class="ua-grid">
        <section class="ua-card"><h2>At a glance</h2><dl class="ua-facts"><dt>First recorded visit</dt><dd>{{ $stats->first_visit ? carbon($stats->first_visit)->format('M j, Y · g:i A') : 'No visits recorded' }}</dd><dt>Last visit activity</dt><dd>{{ $stats->last_activity ? carbon($stats->last_activity)->format('M j, Y · g:i A') : 'No visits recorded' }}</dd><dt>Folders</dt><dd>{{ $stats->folders }} in the saved account state</dd><dt>Media starts</dt><dd>{{ $engagement['media_play'] ?? 'Not recorded' }}</dd><dt>Scores opened</dt><dd>{{ $engagement['score_opened'] ?? 'Not recorded' }}</dd></dl><p class="ua-meta">Saved state and recorded actions are different measures. A missing event does not prove that a feature was never used.</p></section>
        <section class="ua-card"><h2>Frequently opened pieces</h2>@include('admin.pages.analytics.users.pieces', ['items' => $frequent])<p class="ua-meta">Synthesia starts: {{ $engagement['synthesia'] ?: 'Not recorded' }}</p></section>
    </div>
    <section class="ua-card"><div class="ua-section-heading"><div><h2>Recent visits</h2><p>{{ $visitSort->description() }}</p></div><a href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits', 'visit_sort' => $visitSort->field, 'visit_order' => $visitSort->order]) }}">All visits →</a></div>@include('admin.pages.analytics.users.visits')</section>
    @elseif($tab === 'visits')
    @include('admin.pages.analytics.users.filters', ['profileFilters' => true])
    <section class="ua-card"><h2>Visit history</h2>@include('admin.pages.analytics.users.visits')</section>
    @if($stats->ungrouped)
    <section class="ua-card" id="ungrouped-history">
        <div class="ua-section-heading"><div><h2>Ungrouped activity</h2><p>Older app requests and imported activity do not contain visit boundaries or duration. Legacy account associations may be unverified.</p></div><span class="ua-badge">{{ number_format($history->total()) }} {{ $history->total() == 1 ? 'record' : 'records' }} in this period</span></div>
        <p class="ua-meta">{{ number_format($stats->ungrouped) }} {{ $stats->ungrouped == 1 ? 'record' : 'records' }} stored for this account.@if($stats->last_ungrouped_activity) Latest dated activity: {{ carbon($stats->last_ungrouped_activity)->format('M j, Y') }}. <a href="{{ $ungroupedHistoryUrl }}">View latest activity →</a>@endif</p>
        @forelse($history as $event)
        <div class="ua-history-row"><time>{{ \App\Activity\Time::date($event, 'occurred_at')->format('M j, Y · g:i:s A') }}</time><strong>{{ \App\Activity\Presentation::platform($event->platform) }} · {{ \App\Activity\Presentation::page($event) }}</strong><span class="ua-meta">{{ $event->url ?: $event->route_name }}</span></div>
        @empty<p class="ua-muted">No dated ungrouped activity in the selected period and platform.</p>@endforelse
        {{ $history->fragment('ungrouped-history')->links() }}
    </section>
    @endif
    @elseif($tab === 'music')
    <p class="ua-note">Exploration and media sections describe recorded history. Favorites, folders and markings below describe the account’s current saved state.</p>
    <div class="ua-grid">
        <section class="ua-card"><h2>Recently opened pieces</h2>@include('admin.pages.analytics.users.pieces', ['items' => $music['recent']]){{ $music['recent']->links() }}</section>
        <section class="ua-card"><h2>Frequently opened</h2>@include('admin.pages.analytics.users.pieces', ['items' => $frequent])</section>
        <section class="ua-card"><h2>{{ $selectedFolder ? $selectedFolder->name : 'Favorites' }}</h2><p class="ua-meta">Currently saved pieces{{ $selectedFolder ? ' in this folder' : ', deduplicated across folders' }}.</p>@include('admin.pages.analytics.users.pieces', ['items' => $music['favorites']]){{ $music['favorites']->links() }}</section>
        <section class="ua-card"><h2>Folders & collections</h2><ul class="ua-piece-list">@forelse($music['folders'] as $folder)<li><div><a href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'music', 'folder' => $folder->id]) }}">{{ $folder->name }}</a><small>Last updated {{ carbon($folder->updated_at)->format('M j, Y') }}</small></div><span>{{ $folder->pieces_count }} pieces</span></li>@empty<li class="ua-muted">No saved folders.</li>@endforelse</ul>{{ $music['folders']->links() }}</section>
        <section class="ua-card"><h2>Scores & annotations</h2><p class="ua-meta">Last modification dates do not establish the original creation time. Private marking contents are omitted.</p>@include('admin.pages.analytics.users.pieces', ['items' => $music['annotations']]){{ $music['annotations']->links() }}</section>
        <section class="ua-card"><h2>Media engagement</h2>@forelse($music['media'] as $event)@php($m = \App\Activity\Presentation::metadata($event))<div class="ua-history-row"><strong>{{ \App\Activity\Presentation::action($event) }}</strong><small>{{ \App\Activity\Time::event($event)->format('M j, g:i A') }}</small>@if(isset($pieces[$m['piece_id'] ?? '']))<a href="{{ route('webapp.pieces.show', $m['piece_id']) }}" target="_blank" rel="noopener noreferrer">{{ $pieces[$m['piece_id']]->name }}</a>@endif<p class="ua-meta">{{ \App\Activity\Time::duration(isset($m['played_seconds']) ? $m['played_seconds'] * 1000 : null) }} playing · {{ isset($m['unique_watched_percent']) ? round($m['unique_watched_percent']).'% unique content' : 'Coverage unavailable' }}</p></div>@empty<p class="ua-muted">No detailed media engagement recorded.</p>@endforelse{{ $music['media']->links() }}</section>
    </div>
    @else
    <div class="ua-grid"><section class="ua-card"><h2>Account details</h2><dl class="ua-facts"><dt>Registered</dt><dd>{{ $user->created_at->format('M j, Y · g:i A') }}</dd><dt>Signup origin</dt><dd>{{ $user->origin ?: 'Not provided' }}</dd><dt>Country</dt><dd>{{ optional($user->location)->countryName ?: 'Not provided' }}</dd><dt>Gender</dt><dd>{{ $user->gender ?: 'Not provided' }}</dd><dt>Email confirmation</dt><dd>{{ $user->email_verified_at ? $user->email_verified_at->format('M j, Y') : 'Not confirmed' }}</dd><dt>Membership</dt><dd>{{ $status }}</dd>
        @if($source)<dt>Source</dt><dd>{{ class_basename($source) }}</dd><dt>Current record created</dt><dd>{{ $source->created_at->format('M j, Y') }}</dd><dt>Renews / access ends</dt><dd>{{ optional($source->membership_ends_at ?: $source->renews_at)->format('M j, Y') ?: 'Not recorded' }}</dd>@endif
        </dl><a class="btn btn-light" href="{{ route('admin.users.show', $user->id) }}">Manage account & subscription →</a></section>
        <section class="ua-card"><h2>Recorded account milestones</h2><p class="ua-meta">Confirmed events only. Older trial or payment history may exist in subscription management without a corresponding analytics event.</p>@forelse($accountEvents as $event)<div class="ua-history-row"><strong>{{ \App\Activity\Presentation::action($event) }}</strong><time>{{ \App\Activity\Time::event($event)->format('M j, Y · g:i A') }}</time></div>@empty<p class="ua-muted">No account milestones have been recorded.</p>@endforelse{{ $accountEvents->links() }}</section></div>
    @endif
    @if($stats->ungrouped && $tab !== 'visits')<p class="ua-note">{{ number_format($stats->ungrouped) }} additional account-associated records have no visit boundaries. Legacy mobile/imported associations may be unverified. They are not included in visit counts or reconstructed as sessions. <a href="{{ $ungroupedHistoryUrl }}">Browse recorded activity →</a></p>@endif
</div></div>
@endsection
