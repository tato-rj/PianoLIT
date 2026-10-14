@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'User analytics', 'subtitle' => 'Recorded visits, page views and user activity.'])
    @include('admin.pages.analytics.users.nav', ['active' => 'activity'])
    @include('admin.pages.analytics.users.filters')
    <div class="ua-metrics {{ $iosOnly ? 'ua-metrics-three' : ($appActivity ? 'ua-metrics-six' : '') }}">
        @foreach($metrics as $label => $value)
        <article class="ua-card ua-metric"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong>@if($label === 'Reported accounts')<small>Submitted identity · unverified</small>@endif</article>
        @endforeach
    </div>
    @if($appActivity)<p class="ua-note">{{ number_format($appActivity->current_requests) }} iOS requests from the current SQL logger and {{ number_format($appActivity->imported_requests) }} imported requests match this period.
        @if($appActivity->latest_current_request)Latest current-logger request: {{ carbon($appActivity->latest_current_request)->format('M j, Y · g:i:s A') }}.@else No requests from the current SQL logger are recorded in this period.@endif
        These logs do not record visits, confirmed piece openings or playback. Reported account identities are unverified.</p>@endif
    <section class="ua-card ua-trend">
        <div class="ua-section-heading"><div><h2>Daily activity</h2><p>{{ $iosOnly ? 'Recorded iOS requests each day' : ($appActivity ? 'Browser visits and engagement, plus iOS requests each day' : 'Visits, pieces opened and media starts each day') }}</p></div>
            <ul class="ua-chart-legend" aria-label="Chart series">@foreach($series as [$label, $tone])<li><span class="ua-chart-swatch ua-chart-series--{{ $tone }}" aria-hidden="true"></span>{{ $label }}</li>@endforeach</ul>
        </div>
        @include('admin.pages.analytics.users.daily-activity-chart', ['trend' => $trend, 'series' => $series])
        <details class="ua-details"><summary>Daily counts and metric definitions</summary>
            @unless($iosOnly)<p>Visits are counted on their start date. Pieces opened count successful piece-page openings, including repeat openings. Media starts count recorded playback starts, not completed videos; older records can include repeated starts. Browser counts use visits started in this period and events recorded in this period.</p>@endunless
            @if($appActivity)<p>iOS requests count dated API request records from SQL and imported history, using their original occurrence date. Requests may be unsuccessful. Reported accounts count distinct submitted account IDs, not verified visitors. Undated imports and old daily counters are not added.</p>@endif
            <div class="ua-table-scroll"><table class="ua-table ua-chart-table"><thead><tr><th scope="col">Date</th>@foreach($series as [$label, $tone])<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>@foreach($trend as $day)<tr><th scope="row">{{ $day['day'] }}</th>@foreach($series as $key => $definition)<td>{{ number_format($day[$key]) }}</td>@endforeach</tr>@endforeach</tbody></table></div>
        </details>
    </section>
    @unless($iosOnly)<section class="ua-card">
        <div class="ua-section-heading"><div><h2>Recent visits</h2><p>{{ $visitSort->description() }}</p></div><span class="ua-badge">{{ number_format($visits->total()) }} visits</span></div>
        @include('admin.pages.analytics.users.visits')
    </section>@endunless
    <aside class="ua-note"><strong>Some history has fewer details.</strong> {{ number_format($ungroupedCount) }} ungrouped records match this period, including legacy iOS requests and imports. They do not establish visits, verified visitors or watch duration.
        @unless($iosOnly)<a href="{{ request()->fullUrlWithQuery(['history' => 1]) }}">Browse ungrouped history</a>@endunless
    </aside>
    @if($history)
    <section class="ua-card"><h2>{{ $iosOnly ? 'Recent iOS requests' : 'Ungrouped history' }}</h2><p class="ua-meta">Submitted legacy account associations are unverified. Imported daily counters are not added to these records.</p>
        @forelse($history as $event)<div class="ua-history-row"><time>{{ \App\Activity\Time::event($event)->format('M j, Y · g:i:s A') }}</time><span>{{ \App\Activity\Presentation::platform($event->platform) }} · {{ \App\Activity\Presentation::page($event) }}</span><small>{{ $event->user_id ? 'Reported account #'.$event->user_id.' · association unverified' : 'Account unknown' }}</small></div>@empty<p>No dated {{ $iosOnly ? 'iOS requests' : 'ungrouped records' }} in this period.</p>@endforelse
        {{ $history->links() }}
    </section>
    @endif
</div></div>
@endsection
