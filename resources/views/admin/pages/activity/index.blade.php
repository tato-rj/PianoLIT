@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'users', 'title' => $user ? $user->full_name.' · Activity' : 'Visit timelines', 'subtitle' => 'Website and web app activity, grouped by visit.'])
    <p><a href="{{ route('admin.users.logs') }}">Activity overview</a> · <a href="{{ route('admin.activity.index') }}">All visits</a> · <a href="{{ route('admin.activity.index', ['guests' => 1]) }}">Anonymous visits</a></p>
    <div class="card mb-4"><div class="card-body">
        <h4>Visits</h4>
        <div class="table-responsive"><table class="table"><thead><tr><th>Started</th><th>Visitor</th><th>Platform</th><th>Last activity</th><th>Events</th><th></th></tr></thead><tbody>
        @forelse($visits as $visit)
        <tr><td>{{ $visit->started_at }}</td><td>{{ $visit->user_id ? trim($visit->first_name.' '.$visit->last_name).' (#'.$visit->user_id.')' : 'Anonymous · '.substr($visit->visitor_id, 0, 8) }}</td><td>{{ $visit->platform }}</td><td>{{ $visit->last_activity_at }}</td><td>{{ $visit->events_count }}</td><td><a href="{{ route('admin.activity.show', $visit->id) }}">Open timeline</a></td></tr>
        @empty<tr><td colspan="6">No visits recorded yet.</td></tr>@endforelse
        </tbody></table></div>
        {{ $visits->appends(request()->query())->links() }}
    </div></div>
    <div class="card"><div class="card-body">
        <h4>Historical and ungrouped requests</h4>
        <p class="text-muted">Redis imports preserve the original timestamp and payload. Their visit, duration and action outcome are unknown. Current iOS API requests also appear here and in App Logs; the app does not yet report visits or playback timing.</p>
        @include('admin.pages.activity.events', ['events' => $legacy, 'visit' => null])
        {{ $legacy->appends(request()->query())->links() }}
    </div></div>
</div></div>
@endsection
