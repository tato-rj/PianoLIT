<nav class="ua-tabs" aria-label="User analytics">
    @foreach(['activity' => ['admin.analytics.users', 'Activity'], 'people' => ['admin.analytics.users.people', 'People'], 'insights' => ['admin.analytics.users.insights', 'Insights']] as $key => $item)
    <a href="{{ route($item[0]) }}" @if($active === $key) aria-current="page" @endif>{{ $item[1] }}</a>
    @endforeach
</nav>
<p class="ua-note">These reports focus on behavior inside the web app and iOS app. Page views across all hubs, acquisition and retention are in Google Analytics. @include('admin.pages.analytics.users.website-link')</p>
