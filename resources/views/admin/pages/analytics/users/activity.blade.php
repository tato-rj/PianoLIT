@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'User analytics', 'subtitle' => 'Recorded visits, page views and user activity.'])
    @include('admin.pages.analytics.users.nav', ['active' => 'activity'])
    @include('admin.pages.analytics.users.filters')
    <div class="ua-metrics">
        @foreach($metrics as $label => $value)
        <article class="ua-card ua-metric"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></article>
        @endforeach
    </div>
    <section class="ua-card ua-trend">
        <div class="ua-section-heading"><div><h2>Daily visits</h2><p>Visits started each day</p></div><span class="ua-badge">Peak {{ max(array_column($trend, 'total')) }} visits / day</span></div>
        @include('admin.pages.analytics.users.daily-visits-chart', ['trend' => $trend])
        <details class="ua-details"><summary>Daily counts and metric definitions</summary><p>Unique visitors count confirmed accounts once across linked visits, otherwise distinct browser-session visitors. Returning visitors have an earlier recorded visit. Media starts count play/resume events, rather than completed videos. Counts use visits started in this period and events recorded in this period. iOS requests without visits are excluded.</p><div class="ua-daily-counts">@foreach($trend as $day)<span>{{ $day['date'] }} <strong>{{ $day['total'] }}</strong></span>@endforeach</div></details>
    </section>
    <section class="ua-card">
        <div class="ua-section-heading"><div><h2>Recent visits</h2><p>{{ $visitSort->description() }}</p></div><span class="ua-badge">{{ number_format($visits->total()) }} visits</span></div>
        @include('admin.pages.analytics.users.visits')
    </section>
    <aside class="ua-note"><strong>Some history has fewer details.</strong> {{ number_format($ungroupedCount) }} ungrouped records match this period, including legacy iOS requests and imports. They do not establish visits, verified visitors or watch duration.
        <a href="{{ request()->fullUrlWithQuery(['history' => 1]) }}">Browse ungrouped history</a>
    </aside>
    @if($history)
    <section class="ua-card"><h2>Ungrouped history</h2><p class="ua-meta">Submitted legacy account associations are unverified. Imported daily counters are not added to these records.</p>
        @forelse($history as $event)<div class="ua-history-row"><time>{{ \App\Activity\Time::event($event)->format('M j, g:i:s A') }}</time><span>{{ \App\Activity\Presentation::platform($event->platform) }} · {{ \App\Activity\Presentation::page($event) }}</span><small>{{ $event->user_id ? 'Reported account #'.$event->user_id.' · association unverified' : 'Account unknown' }}</small></div>@empty<p>No dated ungrouped records in this period.</p>@endforelse
        {{ $history->links() }}
    </section>
    @endif
</div></div>
@endsection
