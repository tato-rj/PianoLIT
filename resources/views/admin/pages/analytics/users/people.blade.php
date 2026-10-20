@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['breadcrumbs' => [['label' => 'User analytics', 'url' => route('admin.analytics.users')]], 'breadcrumbLabel' => 'People', 'title' => 'User analytics', 'subtitle' => 'Registered accounts and recorded activity.'])
    @include('admin.pages.analytics.users.nav', ['active' => 'people'])
    @component('admin.components.filters.form', ['id' => 'people-filters', 'label' => 'People filters'])
        @input(['bag' => 'default', 'id' => 'people-search', 'name' => 'search', 'type' => 'search', 'label' => 'Find someone', 'placeholder' => 'Name or email', 'value' => request('search'), 'limit' => 160, 'required' => false, 'grid' => 'admin-filter-search'])
        @select(['bag' => 'default', 'id' => 'people-sort', 'name' => 'sort', 'label' => 'Sort by', 'choices' => ['last_active' => 'Last activity', 'registered' => 'Registration date', 'engagement' => 'Pieces opened'], 'value' => request('sort', 'last_active'), 'required' => false])
        <details class="admin-filter-options" @if(request()->hasAny(['origin', 'country', 'status', 'activity', 'registered_from', 'registered_to', 'active_from', 'active_to'])) open @endif><summary>More filters</summary><div class="admin-filter-fields">
            @select(['bag' => 'default', 'id' => 'people-origin', 'name' => 'origin', 'label' => 'Signup platform', 'choices' => ['' => 'Any platform', 'web' => 'Website', 'webapp' => 'Webapp', 'ios' => 'iOS', 'android' => 'Android'], 'value' => request('origin'), 'required' => false])
            @input(['bag' => 'default', 'id' => 'people-country', 'name' => 'country', 'label' => 'Country code', 'limit' => 2, 'placeholder' => 'US', 'value' => request('country'), 'required' => false])
            @select(['bag' => 'default', 'id' => 'people-status', 'name' => 'status', 'label' => 'Membership', 'choices' => ['' => 'All statuses'] + array_combine(['Active', 'Trial', 'Grace period', 'Super user', 'Expired or inactive', 'No membership'], ['Active', 'Trial', 'Grace period', 'Super user', 'Expired or inactive', 'No membership']), 'value' => request('status'), 'required' => false])
            @select(['bag' => 'default', 'id' => 'people-activity', 'name' => 'activity', 'label' => 'Recorded activity', 'choices' => ['' => 'Any activity', 'none' => 'No recorded activity', 'one' => 'One browser visit', 'repeat' => 'Repeat browser visits'], 'value' => request('activity'), 'required' => false])
            @include('admin.components.filters.date-range', ['label' => 'Registration dates', 'showLabel' => true, 'placeholder' => 'Any date', 'fromName' => 'registered_from', 'toName' => 'registered_to', 'from' => request('registered_from', ''), 'to' => request('registered_to', '')])
            @include('admin.components.filters.date-range', ['label' => 'Activity dates', 'showLabel' => true, 'placeholder' => 'Any date', 'fromName' => 'active_from', 'toName' => 'active_to', 'from' => request('active_from', ''), 'to' => request('active_to', '')])
        </div></details>
    @endcomponent
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
