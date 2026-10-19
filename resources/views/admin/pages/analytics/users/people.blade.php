@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'User analytics', 'subtitle' => 'Registered accounts and recorded activity.'])
    @include('admin.pages.analytics.users.nav', ['active' => 'people'])
    <form class="ua-filters ua-directory-filters" method="GET" aria-label="People filters">
        <label class="ua-search">Find someone<input type="search" name="search" placeholder="Name or email" value="{{ request('search') }}" maxlength="160"></label>
        <label>Sort by<select name="sort">@foreach(['last_active' => 'Last activity', 'registered' => 'Registration date', 'engagement' => 'Pieces opened'] as $value => $label)<option value="{{ $value }}" @if(request('sort', 'last_active') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
        <button class="ua-button" type="submit">Search people</button>
        <details class="ua-directory-options" @if(request()->hasAny(['origin', 'country', 'status', 'activity', 'registered_from', 'registered_to', 'active_from', 'active_to'])) open @endif><summary>More filters</summary><div class="ua-filters">
            <label>Signup platform<select name="origin"><option value="">Any platform</option>@foreach(['web' => 'Website', 'webapp' => 'Webapp', 'ios' => 'iOS', 'android' => 'Android'] as $value => $label)<option value="{{ $value }}" @if(request('origin') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
            <label>Country code<input name="country" maxlength="2" placeholder="US" value="{{ request('country') }}"></label>
            <label>Membership<select name="status"><option value="">All statuses</option>@foreach(['Active', 'Trial', 'Grace period', 'Super user', 'Expired or inactive', 'No membership'] as $status)<option @if(request('status') === $status) selected @endif>{{ $status }}</option>@endforeach</select></label>
            <label>Recorded activity<select name="activity"><option value="">Any activity</option>@foreach(['none' => 'No recorded activity', 'one' => 'One browser visit', 'repeat' => 'Repeat browser visits'] as $value => $label)<option value="{{ $value }}" @if(request('activity') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
            @include('admin.components.filters.date-range', ['label' => 'Registration dates', 'showLabel' => true, 'placeholder' => 'Any date', 'fromName' => 'registered_from', 'toName' => 'registered_to', 'from' => request('registered_from', ''), 'to' => request('registered_to', '')])
            @include('admin.components.filters.date-range', ['label' => 'Activity dates', 'showLabel' => true, 'placeholder' => 'Any date', 'fromName' => 'active_from', 'toName' => 'active_to', 'from' => request('active_from', ''), 'to' => request('active_to', '')])
        </div></details>
    </form>
    @component('admin.components.table', [
        'id' => 'analytics-people', 'title' => 'Registered users', 'label' => 'Registered users', 'items' => $people, 'pageSizes' => [],
        'description' => number_format($people->total()).' registered accounts · Includes browser activity and legacy iOS requests. iOS account associations are unverified; visit and piece counts describe browser activity.',
        'columns' => ['Person', 'Registered', 'Last activity', 'Browser visits', 'iOS requests', 'Pieces opened', 'Membership'],
        'emptyTitle' => 'No matching users', 'emptyDescription' => 'Try a different name, email or filter.',
    ])
        @foreach($people as $person)
        <tr>
            <td><a class="admin-table__primary ua-person-name" href="{{ route('admin.analytics.users.profile', $person->id) }}">{{ trim($person->first_name.' '.$person->last_name) }}</a><small>{{ $person->email }}</small></td>
            <td>{{ carbon($person->created_at)->format('M j, Y') }}<small>{{ $person->origin ?: 'Origin not recorded' }}</small></td>
            <td>@if($person->last_activity)
                {{ carbon($person->last_activity)->format('M j, g:i A') }}
                <small>{{ $person->last_ios_activity === $person->last_activity ? ($person->last_visit_activity === $person->last_activity ? 'Browser + iOS' : 'iOS request') : 'Browser visit' }}</small>
                @else{{ $person->ios_requests_count ? 'No dated activity' : 'No recorded activity' }}@endif
            </td>
            <td>{{ number_format($person->visits_count) }}</td>
            <td>@if($person->ios_requests_count)<a href="{{ route('admin.analytics.users.profile', ['user' => $person->id, 'tab' => 'visits', 'range' => 'all', 'platform' => 'app']) }}#ungrouped-history" aria-label="{{ number_format($person->ios_requests_count) }} iOS requests for {{ trim($person->first_name.' '.$person->last_name) }}">{{ number_format($person->ios_requests_count) }}</a>@else 0 @endif</td>
            <td>{{ number_format($person->pieces_count) }}</td>
            <td><span class="ua-badge">{{ $person->membership_status }}</span></td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@endsection
