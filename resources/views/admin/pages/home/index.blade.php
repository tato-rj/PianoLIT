@extends('admin.layouts.app')
@section('body-class', 'admin-dashboard-page')

@section('content')
<div class="admin-dashboard">
  <header class="admin-dashboard-header">
    <div><h1>Overview<span class="admin-heading-dot">.</span></h1><p>A little perspective on everything PianoLIT.</p></div>
    <div class="admin-dashboard-tools">
      <span class="admin-today">{{now()->format('l, M j')}}</span>
      @include('admin.layouts.header.notifications')
      @include('admin.layouts.header.account')
    </div>
  </header>

  @manager
  <div class="admin-dashboard-filters">
    <nav class="admin-segmented" aria-label="Activity platform">
      @foreach(['all' => 'All platforms', 'web' => 'Website', 'webapp' => 'Web app', 'app' => 'iOS'] as $key => $label)
      <a href="{{route('admin.home', ['days' => $dashboard['days'], 'platform' => $key])}}" class="{{$dashboard['platform'] === $key ? 'is-active' : ''}}" @if($dashboard['platform'] === $key) aria-current="true" @endif>{{$label}}</a>
      @endforeach
    </nav>
    <nav class="admin-periods" aria-label="Dashboard date range">
      @foreach([3, 7, 30] as $days)
      <a href="{{route('admin.home', ['days' => $days, 'platform' => $dashboard['platform']])}}" class="{{$dashboard['days'] === $days ? 'is-active' : ''}}" @if($dashboard['days'] === $days) aria-current="true" @endif>Last {{$days}} days</a>
      @endforeach
    </nav>
  </div>

  <section aria-labelledby="admin-activity-title">
    <div class="admin-section-heading"><h2 id="admin-activity-title">The PianoLIT pulse</h2><span>{{$dashboard['start']->format('M j')}} – {{$dashboard['end']->format('M j, Y')}}</span></div>
    <div class="admin-activity-grid">
      <article class="admin-glass-card admin-activity-card">
        <div class="admin-activity-summary">
          <div><strong class="admin-big-number">{{number_format($dashboard['total'])}}</strong><p>Recorded events</p></div>
          <div class="admin-activity-comparison">
            @if($dashboard['change'] !== null)
            <span class="admin-change {{$dashboard['change'] < 0 ? 'is-down' : ''}}">@icon($dashboard['change'] < 0 ? 'arrow-down' : 'arrow-up-right', ['mr' => 0]) {{abs($dashboard['change'])}}%</span>
            <small>vs. previous {{$dashboard['days']}} days</small>
            @else
            <span class="admin-change">@icon('activity', ['mr' => 0]) {{$dashboard['total'] ? 'A fresh start' : 'Ready when you are'}}</span>
            <small>No activity in the previous period</small>
            @endif
          </div>
        </div>
        <div class="admin-gradient-banner">
          <span>@icon('users', ['mr' => 0]) Active web accounts</span>
          <strong>{{number_format($dashboard['activeUsers'])}} <small>in this period</small></strong>
        </div>
        <div class="admin-trend-panel">
          <div class="admin-trend-top"><span><i></i> Daily activity</span><a href="{{route('admin.activity.index')}}">View activity @icon('arrow-up-right', ['mr' => 0])</a></div>
          <div class="admin-trend-visual">
            <h3>Activity<br>over time<span>Every visit.<br>Every discovery.</span></h3>
            <div class="admin-chart" role="img" aria-label="Daily recorded events, {{$dashboard['start']->format('M j')}} to {{$dashboard['end']->format('M j')}}">
              @php($maximum = max(1, collect($dashboard['trend'])->max('total')))
              <div class="admin-chart-bars">
                @foreach($dashboard['trend'] as $day)
                <div class="admin-chart-column" title="{{$day['label']}}: {{number_format($day['total'])}} events">
                  <span class="admin-chart-bar" style="--bar-height: {{$day['total'] / $maximum * 100}}%; --bar-hue: {{round(8 + 100 * $loop->index / max(1, $loop->count - 1))}}"></span>
                </div>
                @endforeach
              </div>
              <div class="admin-chart-axis"><span>{{$dashboard['start']->format('M j')}}</span><span>{{$dashboard['end']->format('M j')}}</span></div>
            </div>
          </div>
          @if(!$dashboard['total'])<p class="admin-chart-empty">Activity will appear here as people explore PianoLIT.</p>@endif
          <details class="admin-chart-data"><summary>Daily counts</summary><table><caption class="visually-hidden">Recorded events per day</caption><thead><tr><th scope="col">Date</th><th scope="col">Events</th></tr></thead><tbody>@foreach($dashboard['trend'] as $day)<tr><td>{{$day['label']}}</td><td>{{number_format($day['total'])}}</td></tr>@endforeach</tbody></table></details>
        </div>
        <div class="admin-platform-breakdown">
          @foreach($dashboard['platforms'] as $key => $platform)
          <div><span><i class="admin-platform-dot is-{{$key}}"></i>{{$platform['label']}}</span><strong>{{number_format($platform['total'])}} <small>{{$platform['share']}}%</small></strong></div>
          @endforeach
        </div>
      </article>

      <article class="admin-glass-card admin-engaged-card">
        <div class="admin-card-heading"><h3>Most engaged users</h3>@icon('sparkles', ['mr' => 0])</div>
        <p class="admin-card-caption">By signed-in web activity</p>
        @if($dashboard['engagedUsers']->isNotEmpty())
        <div class="admin-user-podium">
          @foreach($dashboard['engagedUsers']->take(3) as $user)
          <a class="admin-podium-user is-rank-{{$loop->iteration}}" href="{{route('admin.users.show', $user->id)}}">
            <span class="admin-user-avatar">{{mb_substr($user->first_name, 0, 1)}}{{mb_substr($user->last_name, 0, 1)}}<small>{{$loop->iteration}}</small></span>
            <strong>{{$user->first_name}}</strong><span>{{number_format($user->events_count)}} <small>events</small></span>
          </a>
          @endforeach
        </div>
        <ol class="admin-engaged-list">
          @foreach($dashboard['engagedUsers'] as $user)
          <li><a href="{{route('admin.users.show', $user->id)}}"><span class="admin-user-rank">{{str_pad($loop->iteration, 2, '0', STR_PAD_LEFT)}}</span><span class="admin-user-name"><strong>{{trim($user->first_name.' '.$user->last_name)}}</strong><small>View profile</small></span><span class="admin-user-events">{{number_format($user->events_count)}} @icon('arrow-up-right', ['mr' => 0])</span></a></li>
          @endforeach
        </ol>
        @else
        <div class="admin-engaged-empty">@icon('users', ['mr' => 0])<strong>{{$dashboard['platform'] === 'app' ? 'Web activity tells this story' : 'Meet your next regulars'}}</strong><p>{{$dashboard['platform'] === 'app' ? 'Choose All platforms, Website or Web app to see engaged accounts.' : 'Your most active accounts will appear here once they start exploring.'}}</p></div>
        @endif
        <a class="admin-card-footer-link" href="{{route('admin.users.index')}}">Explore all users @icon('arrow-right', ['mr' => 0])</a>
      </article>
    </div>
    <p class="admin-activity-note">Page views, actions and app requests. Timer updates excluded. iOS identity is unverified and excluded from user rankings. Times in {{config('app.timezone')}}.</p>
  </section>

  <section class="admin-metrics-section" aria-labelledby="admin-metrics-title">
    <div class="admin-section-heading"><h2 id="admin-metrics-title">At a glance</h2><span>All-time totals · additions in the last {{$dashboard['days']}} days</span></div>
    <div class="admin-metrics-grid">
      @foreach($dashboard['metrics'] as $metric)
      <a class="admin-glass-card admin-metric-card" href="{{$metric['url']}}">
        <div class="admin-metric-heading"><h3>{{$metric['label']}}</h3><span class="admin-metric-icon is-{{$loop->index}}">@icon($metric['icon'], ['mr' => 0])</span></div>
        <div class="admin-metric-value"><strong>{{number_format($metric['total'])}}</strong><span>+{{number_format($metric['added'])}}</span></div>
        <p>{{$metric['added'] ? 'New in this period' : 'No additions this period'}}</p>
        <div class="admin-metric-progress"><span><i style="width: {{$metric['share']}}%"></i></span><strong>{{$metric['share']}}%</strong></div>
        <small class="admin-metric-footnote">of total added this period</small>
      </a>
      @endforeach
    </div>
  </section>

  <section class="admin-library-section" aria-labelledby="admin-library-title">
    <div class="admin-section-heading"><h2 id="admin-library-title">More to explore</h2><a href="{{route('admin.users.locations.index')}}">User locations @icon('arrow-up-right', ['mr' => 0])</a></div>
    <div class="admin-library-grid">
      @foreach($dashboard['library'] as $item)
      <a class="admin-library-card" href="{{$item['url']}}">@icon($item['icon'], ['mr' => 0])<span>{{$item['label']}}<small>Published</small></span><strong>{{number_format($item['total'])}}</strong>@icon('chevron-right', ['mr' => 0])</a>
      @endforeach
    </div>
  </section>
  @else
  <article class="admin-glass-card admin-editor-welcome"><span class="admin-editor-icon">@icon('music', ['mr' => 0])</span><h2>Welcome, {{auth('admin')->user()->first_name}}.</h2><p>Thank you for helping bring piano music to life.</p><div><strong>{{number_format(auth('admin')->user()->pieces_count)}}</strong> pieces created <span>·</span> <strong>{{number_format(auth('admin')->user()->composers_count)}}</strong> composers created</div></article>
  @endmanager
</div>
@endsection
