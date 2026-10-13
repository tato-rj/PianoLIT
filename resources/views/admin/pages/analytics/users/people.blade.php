@extends('admin.layouts.app')
@section('content')
<div class="content-wrapper"><div class="container-fluid ua">
    @include('admin.components.page.title', ['title' => 'User analytics', 'subtitle' => 'Registered accounts and recorded activity.'])
    @include('admin.pages.analytics.users.nav', ['active' => 'people'])
    <form class="ua-filters ua-directory-filters" method="GET" aria-label="People filters">
        <label class="ua-search">Find someone<input type="search" name="search" placeholder="Name or email" value="{{ request('search') }}" maxlength="160"></label>
        <label>Sort by<select name="sort">@foreach(['last_active' => 'Last activity', 'registered' => 'Registration date', 'engagement' => 'Pieces opened'] as $value => $label)<option value="{{ $value }}" @if(request('sort', 'last_active') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
        <button class="ua-button" type="submit">Search people</button>
        <details class="ua-directory-options" @if(request()->hasAny(['origin', 'country', 'status', 'activity', 'registered_from', 'active_from'])) open @endif><summary>More filters</summary><div class="ua-filters">
            <label>Signup platform<select name="origin"><option value="">Any platform</option>@foreach(['web' => 'Website', 'webapp' => 'Webapp', 'ios' => 'iOS', 'android' => 'Android'] as $value => $label)<option value="{{ $value }}" @if(request('origin') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
            <label>Country code<input name="country" maxlength="2" placeholder="US" value="{{ request('country') }}"></label>
            <label>Membership<select name="status"><option value="">All statuses</option>@foreach(['Active', 'Trial', 'Grace period', 'Super user', 'Expired or inactive', 'No membership'] as $status)<option @if(request('status') === $status) selected @endif>{{ $status }}</option>@endforeach</select></label>
            <label>Visit history<select name="activity"><option value="">Any activity</option>@foreach(['none' => 'No recorded visits', 'one' => 'One visit', 'repeat' => 'Repeat visits'] as $value => $label)<option value="{{ $value }}" @if(request('activity') === $value) selected @endif>{{ $label }}</option>@endforeach</select></label>
            <label>Registered from<input type="date" name="registered_from" value="{{ request('registered_from') }}"></label><label>Registered through<input type="date" name="registered_to" value="{{ request('registered_to') }}"></label>
            <label>Active from<input type="date" name="active_from" value="{{ request('active_from') }}"></label><label>Active through<input type="date" name="active_to" value="{{ request('active_to') }}"></label>
        </div></details>
    </form>
    <section class="ua-card"><div class="ua-section-heading"><div><h2>Registered users</h2><p>{{ number_format($people->total()) }} registered accounts · Visit data excludes unverified mobile history.</p></div></div>
        <div class="ua-table-scroll"><table class="ua-table"><thead><tr><th>Person</th><th>Registered</th><th>Last visit activity</th><th>Visits</th><th>Pieces opened</th><th>Membership</th></tr></thead><tbody>
            @forelse($people as $person)<tr><td><a class="ua-person-name" href="{{ route('admin.analytics.users.profile', $person->id) }}">{{ trim($person->first_name.' '.$person->last_name) }}</a><small>{{ $person->email }}</small></td><td>{{ carbon($person->created_at)->format('M j, Y') }}<small>{{ $person->origin ?: 'Origin not recorded' }}</small></td><td>{{ $person->last_activity ? carbon($person->last_activity)->format('M j, g:i A') : 'No recorded visits' }}</td><td>{{ number_format($person->visits_count) }}</td><td>{{ number_format($person->pieces_count) }}</td><td><span class="ua-badge">{{ $person->membership_status }}</span></td></tr>
            @empty<tr><td colspan="6"><div class="ua-empty"><h3>No matching users</h3><p>Try a different name, email or filter.</p></div></td></tr>@endforelse
        </tbody></table></div>
        {{ $people->links() }}
    </section>
</div></div>
@endsection
