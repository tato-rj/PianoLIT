@php($visitDay = null)
@php($groupVisitsByDate = !isset($visitSort) || $visitSort->byDate())
@isset($visitSort)@include('admin.pages.analytics.users.visit-sort')@endisset
<div class="ua-visits">
@forelse($visits as $item)
    @php($date = \App\Activity\Time::date($item, 'started_at'))
    @if($groupVisitsByDate && $visitDay !== $date->toDateString())
        @php($visitDay = $date->toDateString())
        <h3 class="ua-day">{{ $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('F j, Y')) }}</h3>
    @endif
    <article class="ua-visit">
        <span class="ua-avatar" aria-hidden="true">{{ mb_substr(\App\Activity\Presentation::name($item), 0, 1) }}</span>
        <div class="ua-visit-copy">
            @if($item->account_id && !empty($item->first_name))
            <a class="ua-person-name" href="{{ route('admin.analytics.users.profile', $item->account_id) }}">{{ \App\Activity\Presentation::name($item) }}</a>
            @else<strong>{{ \App\Activity\Presentation::name($item) }}</strong>@endif
            <p class="ua-meta">{{ $date->format($groupVisitsByDate ? 'g:i A' : 'M j, Y · g:i A') }} · {{ \App\Activity\Presentation::platform($item->platform) }} · {{ \App\Activity\Time::visitDuration($item) }} · {{ $item->pages_count }} pages
                @if(isset($visitSort) && $visitSort->byActions()) · {{ $item->actions_count }} {{ $item->actions_count == 1 ? 'action' : 'actions' }}@endif
                @if($item->association_reason)<span class="ua-badge">Linked after sign-in</span>@endif
            </p>
            @include('admin.pages.analytics.users.summary', ['summaryVisit' => $item])
        </div>
        <a class="ua-journey-link" href="{{ route('admin.analytics.users.journey', $item->id) }}">View journey @icon('arrow-up-right', ['mr' => 0])</a>
    </article>
@empty
    <div class="ua-empty">@icon('users', ['mr' => 0])<h3>No visits recorded</h3>
        @if(isset($stats) && $stats->ungrouped)<p>Activity without visit details is available separately. <a href="{{ $tab === 'visits' ? '#ungrouped-history' : $ungroupedHistoryUrl }}">Browse recorded activity →</a></p>
        @else<p>Try a different period or platform. Older ungrouped activity is kept separately.</p>@endif
    </div>
@endforelse
</div>
@include('admin.components.pagination', ['paginator' => $visits, 'label' => 'Visits'])
