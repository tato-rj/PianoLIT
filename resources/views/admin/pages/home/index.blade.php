@extends('admin.layouts.app')
@section('body-class', 'admin-dashboard-page')

@section('head')
@manager
<link rel="stylesheet" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}">
@endmanager
@endsection

@section('content')
<div class="admin-dashboard">
  @include('admin.components.page.title', ['title' => 'Overview', 'subtitle' => 'User activity and site metrics.'])

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
    <div class="admin-section-heading"><h2 id="admin-activity-title">Activity summary</h2><span>{{$dashboard['start']->format('M j')}} – {{$dashboard['end']->format('M j, Y')}}</span></div>
    <div class="admin-activity-grid">
      <article class="admin-glass-card admin-activity-card">
        <div class="admin-activity-summary">
          <div><strong class="admin-big-number">{{number_format($dashboard['total'])}}</strong><p>Recorded events</p></div>
          <div class="admin-activity-comparison">
            @if($dashboard['change'] !== null)
            <span class="admin-change {{$dashboard['change'] < 0 ? 'is-down' : ''}}">@icon($dashboard['change'] < 0 ? 'arrow-down' : 'arrow-up-right', ['mr' => 0]) {{abs($dashboard['change'])}}%</span>
            <small>vs. previous {{$dashboard['days']}} days</small>
            @else
            <span class="admin-change">@icon('activity', ['mr' => 0]) {{$dashboard['total'] ? 'New activity' : 'No recorded activity'}}</span>
            <small>No activity in the previous period</small>
            @endif
          </div>
        </div>
        <div class="admin-gradient-banner">
          <span>@icon('users', ['mr' => 0]) Active web accounts</span>
          <strong>{{number_format($dashboard['activeUsers'])}} <small>in this period</small></strong>
        </div>
        <div class="admin-trend-panel">
          <div class="admin-trend-top"><span><i></i> Daily activity</span><a href="{{route('admin.analytics.users')}}">View activity @icon('arrow-up-right', ['mr' => 0])</a></div>
          <div class="admin-trend-visual">
            <h3>Activity <br>over time<span>Recorded events <br>by day.</span></h3>
            @include('admin.pages.home.activity-chart', ['trend' => $dashboard['trend']])
          </div>
          @if(!$dashboard['total'])<p class="admin-chart-empty">No recorded activity in this period.</p>@endif
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
          <a class="admin-podium-user is-rank-{{$loop->iteration}}" href="{{route('admin.analytics.users.profile', $user->id)}}">
            <span class="admin-user-avatar">{{mb_substr($user->first_name, 0, 1)}}{{mb_substr($user->last_name, 0, 1)}}<small>{{$loop->iteration}}</small></span>
            <strong>{{$user->first_name}}</strong><span>{{number_format($user->events_count)}} <small>events</small></span>
          </a>
          @endforeach
        </div>
        <ol class="admin-engaged-list">
          @foreach($dashboard['engagedUsers'] as $user)
          <li><a href="{{route('admin.analytics.users.profile', $user->id)}}"><span class="admin-user-rank">{{str_pad($loop->iteration, 2, '0', STR_PAD_LEFT)}}</span><span class="admin-user-name"><strong>{{trim($user->first_name.' '.$user->last_name)}}</strong><small>View profile</small></span><span class="admin-user-events">{{number_format($user->events_count)}} @icon('arrow-up-right', ['mr' => 0])</span></a></li>
          @endforeach
        </ol>
        @else
        <div class="admin-engaged-empty">@icon('users', ['mr' => 0])<strong>{{$dashboard['platform'] === 'app' ? 'User rankings unavailable' : 'No active web accounts'}}</strong><p>{{$dashboard['platform'] === 'app' ? 'Choose All platforms, Website or Web app to see engaged accounts.' : 'No signed-in web activity was recorded in this period.'}}</p></div>
        @endif
        <a class="admin-card-footer-link" href="{{route('admin.users.index')}}">View all users @icon('arrow-right', ['mr' => 0])</a>
      </article>
    </div>
    <p class="admin-activity-note">Page views, actions and app requests. Timer updates excluded. iOS identity is unverified and excluded from user rankings. Times in {{config('app.timezone')}}.</p>
  </section>

  <section class="admin-metrics-section" aria-labelledby="admin-metrics-title">
    <div class="admin-section-heading"><h2 id="admin-metrics-title">At a glance</h2><span>Current totals · additions in the last {{$dashboard['days']}} days</span></div>
    <div class="admin-metrics-grid">
      @foreach($dashboard['metrics'] as $metric)
      <a class="admin-glass-card admin-metric-card" href="{{$metric['url']}}">
        <div class="admin-metric-heading"><h3>{{$metric['label']}}</h3><span class="admin-metric-icon is-{{$loop->index}}">@icon($metric['icon'], ['mr' => 0])</span></div>
        <div class="admin-metric-value"><strong>{{number_format($metric['total'])}}</strong><span>+{{number_format($metric['added'])}}</span></div>
        <p>{{$metric['membership'] ? ($metric['added'] ? 'Started in this period' : 'None started this period') : ($metric['added'] ? 'New in this period' : 'No additions this period')}}</p>
        <div class="admin-metric-progress"><span><i style="width: {{$metric['share']}}%"></i></span><strong>{{$metric['share']}}%</strong></div>
        <small class="admin-metric-footnote">{{$metric['membership'] ? 'of total started this period' : 'of total added this period'}}</small>
      </a>
      @endforeach
    </div>
  </section>

  @include('admin.pages.home.user-countries')

  <section class="admin-library-section" aria-labelledby="admin-library-title">
    <div class="admin-section-heading"><h2 id="admin-library-title">Site content</h2><a href="{{route('admin.users.locations.index')}}">User locations @icon('arrow-up-right', ['mr' => 0])</a></div>
    <div class="admin-library-grid">
      @foreach($dashboard['library'] as $item)
      <a class="admin-library-card" href="{{$item['url']}}">@icon($item['icon'], ['mr' => 0])<span>{{$item['label']}}<small>Published</small></span><strong>{{number_format($item['total'])}}</strong>@icon('chevron-right', ['mr' => 0])</a>
      @endforeach
    </div>
  </section>
  @else
  <article class="admin-glass-card admin-editor-welcome"><span class="admin-editor-icon">@icon('music', ['mr' => 0])</span><h2>Welcome, {{auth('admin')->user()->first_name}}.</h2><p>Your contributed content.</p><div><strong>{{number_format(auth('admin')->user()->pieces_count)}}</strong> pieces created <span>·</span> <strong>{{number_format(auth('admin')->user()->composers_count)}}</strong> composers created</div></article>
  @endmanager
</div>
@endsection

@section('scripts')
@manager
<script src="{{ mix('js/views/admin-user-map.js') }}" defer></script>
@endmanager
@endsection
