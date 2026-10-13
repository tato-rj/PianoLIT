@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'Visit journey', 'subtitle' => 'One visit, told through the moments that were recorded.'])
    <nav class="ua-breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('admin.analytics.users') }}">User analytics</a><span>/</span>
        @if($visit->account_id && $visit->first_name)<a href="{{ route('admin.analytics.users.profile', $visit->account_id) }}">{{ \App\Activity\Presentation::name($visit) }}</a><span>/</span>@endif
        <span aria-current="page">Visit journey</span>
    </nav>
    <section class="ua-card ua-identity">
        <span class="ua-avatar ua-avatar-lg" aria-hidden="true">{{ mb_substr(\App\Activity\Presentation::name($visit), 0, 1) }}</span>
        <div><h2>{{ \App\Activity\Presentation::name($visit) }}</h2><p>{{ \App\Activity\Time::date($visit, 'started_at')->format('l, F j, Y') }} · {{ \App\Activity\Presentation::platform($visit->platform) }}</p>
            <p class="ua-meta">{{ \App\Activity\Time::date($visit, 'started_at')->format('g:i:s A') }} to {{ \App\Activity\Time::date($visit, 'last_activity_at')->format('g:i:s A') }} · {{ config('app.timezone') }}</p>
            @if($visit->association_reason)<span class="ua-badge">Anonymous visit linked by confirmed authentication</span>@endif
        </div>
        <div class="ua-visit-facts"><strong>{{ \App\Activity\Time::visitDuration($visit) }}</strong><span>Observed span · {{ $visit->pages_count }} pages</span></div>
    </section>
    <nav class="ua-visit-navigation" aria-label="Adjacent visits">
        @if($previous)<a class="ua-button ua-button-secondary" href="{{ route('admin.analytics.users.journey', $previous) }}">← Previous visit</a>@endif
        @if($next)<a class="ua-button ua-button-secondary" href="{{ route('admin.analytics.users.journey', $next) }}">Next visit →</a>@endif
    </nav>
    <p class="ua-note">Times are shown in {{ config('app.timezone') }}. Foreground visibility does not prove attention. The final timestamp is the last recorded activity, rather than a browser close time.</p>
    @if($unplaced->total())<p class="ua-note">Actions without a recorded page appear at their recorded times below. They are not assigned to a page by guesswork.</p>@endif
    @if($detailCount > 2000)<p class="ua-note">This part of the visit contains {{ number_format($detailCount) }} events. The earliest 2,000 are shown; later page groups remain available through pagination. Details are incomplete.</p>@endif
    <ol class="ua-timeline">
        @forelse($timeline as $group)
        @if($group['kind'] === 'action')
        @php($event = $group['event'])
        <li class="ua-timeline-entry"><time>{{ \App\Activity\Time::event($event)->format('g:i:s A') }}</time><article class="ua-card ua-page"><span class="ua-timeline-marker" aria-hidden="true">@icon(\App\Activity\Presentation::icon($event->type), ['mr' => 0])</span><h2>{{ \App\Activity\Presentation::action($event) }}</h2><p class="ua-meta">No page association recorded.</p>
            @if($event->resource_type === 'piece' && isset($pieces[$event->resource_id]))<a href="{{ route('admin.pieces.edit', $event->resource_id) }}">{{ $pieces[$event->resource_id]->name }}</a>@endif
            @include('admin.pages.analytics.users.details')</article></li>
        @continue
        @endif
        @php($page = $group['page'])
        <li class="ua-timeline-entry">
            <time>{{ \App\Activity\Time::date($page, 'opened_at')->format('g:i:s A') }}</time>
            <article class="ua-card ua-page">
                <span class="ua-timeline-marker" aria-hidden="true">@icon('arrow-up-right', ['mr' => 0])</span>
                <h2>Opened @if($group['piece'])<a href="{{ route('admin.pieces.edit', $group['piece']->id) }}">{{ $group['piece']->name }}</a>@else{{ \App\Activity\Presentation::page($page) }}@endif</h2>
                @if($group['piece'])<p class="ua-meta">{{ $group['piece']->composer }}</p>@endif
                <p class="ua-page-time">{{ $group['observed'] ? 'Page visible for '.\App\Activity\Time::duration($page->visible_ms) : 'Page visibility was not recorded' }}</p>
                <ul class="ua-actions">
                    @foreach($group['actions']->reject(function ($e) { return $e->type === 'media_progress'; }) as $event)
                    <li><span class="ua-action-icon" aria-hidden="true">@icon(\App\Activity\Presentation::icon($event->type), ['mr' => 0])</span><div>
                        <div class="ua-action-line"><strong>{{ \App\Activity\Presentation::action($event) }}</strong><time>{{ \App\Activity\Time::event($event)->format('g:i:s A') }}</time></div>
                        @if($event->resource_type === 'piece' && isset($pieces[$event->resource_id]) && (!$group['piece'] || $group['piece']->id != $event->resource_id))<a href="{{ route('admin.pieces.edit', $event->resource_id) }}">{{ $pieces[$event->resource_id]->name }}</a>@endif
                        @php($m = \App\Activity\Presentation::metadata($event))
                        @if($event->type === 'score_progress')<p class="ua-meta">Score visible for {{ \App\Activity\Time::duration($m['visible_ms'] ?? null) }} so far</p>@endif
                        @if($event->type === 'annotation_saved')<p class="ua-meta">{{ $m['created_count'] ?? 0 }} created · {{ $m['edited_count'] ?? 0 }} edited · {{ $m['removed_count'] ?? 0 }} removed. Marking contents are private.</p>@endif
                        @include('admin.pages.analytics.users.details')
                    </div></li>
                    @endforeach
                </ul>
                @foreach($group['media'] as $playback)
                <div class="ua-playback"><strong>{{ $playback['label'] }}</strong>
                    <p>{{ \App\Activity\Time::duration(isset($playback['watched']) ? $playback['watched'] * 1000 : null) }} playing
                        @if($playback['coverage'] !== null) · {{ round($playback['coverage']) }}% unique content played @else · Unique watch coverage unavailable @endif
                    </p>
                    @if($playback['coverage'] !== null)<meter min="0" max="100" value="{{ min(100, max(0, $playback['coverage'])) }}" aria-label="Unique content played">{{ round($playback['coverage']) }}%</meter>@endif
                    @if($playback['seeked'])<small>Seeking was recorded. Skipped content is excluded from new coverage estimates.</small>@endif
                </div>
                @endforeach
                @if($group['actions']->isEmpty())<p class="ua-meta">Limited activity details were recorded for this page.</p>@endif
                <details class="ua-details"><summary>Page details</summary><p>{{ $page->url }}</p><p>Observed elapsed time: {{ $group['observed'] ? \App\Activity\Time::duration($page->elapsed_ms) : 'Not recorded' }}</p></details>
            </article>
        </li>
        @empty<li class="ua-empty"><h2>Limited activity details were recorded for this visit.</h2><p>No page boundaries are available. Any independently recorded actions appear below.</p></li>@endforelse
    </ol>
    {{ $pages->links() }}
    @if($unplaced->total()){{ $unplaced->links() }}@endif
    <section class="ua-card ua-summary"><span class="ua-eyebrow">The recorded story</span><h2>{{ $visit->summary }}</h2><p>Last recorded activity: {{ \App\Activity\Time::date($visit, 'last_activity_at')->format('M j, g:i:s A') }}. No further activity recorded.</p></section>
</div></div>
@endsection
