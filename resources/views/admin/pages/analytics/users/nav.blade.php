<nav class="ua-tabs" aria-label="User analytics">
    @foreach(['activity' => ['admin.analytics.users', 'Activity'], 'people' => ['admin.analytics.users.people', 'People'], 'insights' => ['admin.analytics.users.insights', 'Insights']] as $key => $item)
    <a href="{{ route($item[0]) }}" @if($active === $key) aria-current="page" @endif>{{ $item[1] }}</a>
    @endforeach
</nav>
