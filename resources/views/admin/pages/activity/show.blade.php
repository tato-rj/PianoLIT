@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => 'Visit timeline', 'subtitle' => ($user ? $user->full_name : ($visit->user_id ? 'Deleted account #'.$visit->user_id : 'Anonymous visitor')).' · '.$visit->platform])
    <p><a href="{{ $user ? route('admin.users.activity', $user) : route('admin.activity.index') }}">Back to visits</a></p>
    @php($startedAt = carbon($visit->started_at))
    @php($lastActivity = carbon($visit->last_activity_at))
    <p>{{ $startedAt->format('l, F j, Y') }} · {{ $startedAt->format('g:i:s A') }} → {{ $lastActivity->format($startedAt->isSameDay($lastActivity) ? 'g:i:s A' : 'l, F j, Y · g:i:s A') }}</p>
    <div class="card mb-4"><div class="card-body">
        <h4>Pages opened</h4>
        <div class="table-responsive"><table class="table"><thead><tr><th>Opened</th><th>Page</th><th>Observed time</th><th>Visible time</th></tr></thead>
        @forelse($pages->groupBy(function ($page) { return carbon($page->opened_at)->toDateString(); }) as $day => $dayPages)
        <tbody>
            @include('admin.pages.activity.day-heading', ['columns' => 4])
            @foreach($dayPages as $page)
                <tr><td class="text-nowrap">{{ carbon($page->opened_at)->format('g:i:s A') }}</td><td class="text-break">{{ $page->url }}</td><td>{{ round($page->elapsed_ms / 1000, 1) }} s</td><td>{{ round($page->visible_ms / 1000, 1) }} s</td></tr>
            @endforeach
        </tbody>
        @empty<tbody><tr><td colspan="4">No pages recorded.</td></tr></tbody>@endforelse
        </table></div>
        <p class="text-muted small">Times are browser estimates. Visible time measures an open foreground page; it does not prove attention. Closing or losing connectivity can prevent the final event from arriving.</p>
    </div></div>
    <div class="card"><div class="card-body">
        <h4>Sequence of activity</h4>
        @include('admin.pages.activity.events')
        {{ $events->links() }}
    </div></div>
</div></div>
@endsection
