@php($visitDay = null)
<div class="ua-visits">
@forelse($visits as $item)
    @php($date = \App\Activity\Time::date($item, 'started_at'))
    @if($visitDay !== $date->toDateString())
        @php($visitDay = $date->toDateString())
        <h3 class="ua-day">{{ $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('F j, Y')) }}</h3>
    @endif
    <article class="ua-visit">
        <span class="ua-avatar" aria-hidden="true">{{ mb_substr(\App\Activity\Presentation::name($item), 0, 1) }}</span>
        <div class="ua-visit-copy">
            @if($item->account_id && !empty($item->first_name))
            <a class="ua-person-name" href="{{ route('admin.analytics.users.profile', $item->account_id) }}">{{ \App\Activity\Presentation::name($item) }}</a>
            @else<strong>{{ \App\Activity\Presentation::name($item) }}</strong>@endif
            <p class="ua-meta">{{ $date->format('g:i A') }} · {{ \App\Activity\Presentation::platform($item->platform) }} · {{ \App\Activity\Time::visitDuration($item) }} · {{ $item->pages_count }} pages
                @if($item->association_reason)<span class="ua-badge">Linked after sign-in</span>@endif
            </p>
            <p>{{ $item->summary }}</p>
        </div>
        <a class="ua-journey-link" href="{{ route('admin.analytics.users.journey', $item->id) }}">View journey @icon('arrow-up-right', ['mr' => 0])</a>
    </article>
@empty
    <div class="ua-empty">@icon('users', ['mr' => 0])<h3>No visits here yet</h3><p>Try a different period or platform. Older ungrouped activity is kept separately.</p></div>
@endforelse
</div>
{{ $visits->links() }}
