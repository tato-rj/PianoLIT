@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @php
        $journeyBreadcrumbs = [['label' => 'User analytics', 'url' => route('admin.analytics.users')]];
        if ($visit->account_id && $visit->first_name) {
            $journeyBreadcrumbs[] = ['label' => \App\Activity\Presentation::name($visit), 'url' => route('admin.analytics.users.profile', $visit->account_id)];
        }
    @endphp
    @component('admin.components.page.title', ['breadcrumbs' => $journeyBreadcrumbs, 'title' => 'Visit journey', 'subtitle' => 'Recorded pages and actions for this visit.'])
        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#delete-visit-modal" aria-label="Delete visit" title="Delete visit">@icon('trash-2', ['mr' => 0])</button>
    @endcomponent
    <section class="ua-card ua-summary"><h2 class="ua-eyebrow">Visit summary</h2>@include('admin.pages.analytics.users.summary', ['summaryVisit' => $visit])<p>Last recorded activity: {{ \App\Activity\Time::date($visit, 'last_activity_at')->format('M j, g:i:s A') }}. No further activity recorded.</p></section>
    <section class="ua-card ua-identity">
        <span class="ua-avatar ua-avatar-lg" aria-hidden="true">{{ mb_substr(\App\Activity\Presentation::name($visit), 0, 1) }}</span>
        <div><h2>{{ \App\Activity\Presentation::name($visit) }}</h2><p>{{ \App\Activity\Time::date($visit, 'started_at')->format('l, F j, Y') }} · {{ \App\Activity\Presentation::platform($visit->platform) }}</p>
            <p class="ua-meta">{{ \App\Activity\Time::date($visit, 'started_at')->format('g:i:s A') }} to {{ \App\Activity\Time::date($visit, 'last_activity_at')->format('g:i:s A') }}</p>
            @if($visit->association_reason)<span class="ua-badge">Anonymous visit linked by confirmed authentication</span>@endif
        </div>
        <div class="ua-visit-facts"><strong>{{ \App\Activity\Time::visitDuration($visit) }}</strong><span>Observed span · {{ $visit->pages_count }} pages</span></div>
    </section>
    <nav class="ua-visit-navigation" aria-label="Adjacent visits">
        @if($previous)<a class="btn btn-light" href="{{ route('admin.analytics.users.journey', $previous) }}">← Previous visit</a>@endif
        @if($next)<a class="btn btn-light" href="{{ route('admin.analytics.users.journey', $next) }}">Next visit →</a>@endif
    </nav>
    <p class="ua-note">Foreground visibility does not prove attention. The final timestamp is the last recorded activity, rather than a browser close time.</p>
    @if($unplaced->total())<p class="ua-note">Actions without a recorded page appear at their recorded times below. They are not assigned to a page by guesswork.</p>@endif
    @if($detailCount > 2000)<p class="ua-note">This part of the visit contains {{ number_format($detailCount) }} events. The earliest 2,000 are shown; later page groups remain available through pagination. Details are incomplete.</p>@endif
    <ol class="ua-timeline">
        @forelse($timeline as $group)
        @if($group['kind'] === 'action')
        @php($event = $group['event'])
        <li class="ua-timeline-entry"><time>{{ \App\Activity\Time::event($event)->format('g:i:s A') }}</time><article class="ua-card ua-page"><span class="ua-timeline-marker" aria-hidden="true">@icon(\App\Activity\Presentation::icon($event->type, \App\Activity\Presentation::metadata($event)), ['mr' => 0])</span><h2>{{ \App\Activity\Presentation::action($event, $pieces) }}</h2><p class="ua-meta">No page association recorded.</p>
            @php($actionPieceId = \App\Activity\Presentation::pieceSelectionId($event) ?: ($event->resource_type === 'piece' ? $event->resource_id : (\App\Activity\Presentation::metadata($event)['piece_id'] ?? null)))
            @if($actionPieceId && isset($pieces[$actionPieceId]))<a href="{{ route('webapp.pieces.show', $actionPieceId) }}" target="_blank" rel="noopener noreferrer">{{ $pieces[$actionPieceId]->name }}</a>@endif
            @include('admin.pages.analytics.users.details')</article></li>
        @continue
        @endif
        @php($page = $group['page'])
        <li class="ua-timeline-entry">
            <time>{{ \App\Activity\Time::date($page, 'opened_at')->format('g:i:s A') }}</time>
            <article class="ua-card ua-page">
                <span class="ua-timeline-marker" aria-hidden="true">@icon('arrow-up-right', ['mr' => 0])</span>
                <h2>Opened @if($group['piece'])<a href="{{ route('webapp.pieces.show', $group['piece']->id) }}" target="_blank" rel="noopener noreferrer">{{ $group['piece']->name }}</a>@else{{ \App\Activity\Presentation::page($page) }}@endif</h2>
                @if($group['piece'])<p class="ua-meta">{{ $group['piece']->composer }}</p>@endif
                <p class="ua-page-time">{{ $group['observed'] ? 'Page visible for '.\App\Activity\Time::duration($page->visible_ms) : 'Page visibility was not recorded' }}</p>
                <ul class="ua-actions">
                    @include('admin.pages.analytics.users.moments', ['items' => $group['displayMoments']])
                </ul>
                @if($group['actions']->isEmpty() && $group['media']->isEmpty())<p class="ua-meta">Limited activity details were recorded for this page.</p>@endif
                <details class="ua-details"><summary>Page details</summary><p>{{ $page->url }}</p><p>Observed elapsed time: {{ $group['observed'] ? \App\Activity\Time::duration($page->elapsed_ms) : 'Not recorded' }}</p></details>
            </article>
        </li>
        @empty<li class="ua-empty"><h2>Limited activity details were recorded for this visit.</h2><p>No page boundaries are available. Any independently recorded actions appear below.</p></li>@endforelse
    </ol>
    @include('admin.components.pagination', ['paginator' => $pages, 'label' => 'Visit pages'])
    @if($unplaced->total())@include('admin.components.pagination', ['paginator' => $unplaced, 'label' => 'Additional activity'])@endif
</div></div>
@component('components.modal', ['id' => 'delete-visit-modal', 'header' => 'Delete this visit?', 'centered' => true, 'descriptionId' => 'delete-visit-description', 'options' => ['body' => ['padding' => 3]]])
    @slot('body')
        <p>{{ \App\Activity\Presentation::name($visit) }} · {{ \App\Activity\Time::date($visit, 'started_at')->format('M j, Y, g:i A') }}</p>
        <p id="delete-visit-description" class="mb-0">Permanently delete this visit and all its recorded pages and actions. This cannot be undone.</p>
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="{{ route('admin.analytics.users.visits.destroy', $visit->id) }}" class="m-0">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete visit</button>
        </form>
    @endslot
@endcomponent
@endsection
