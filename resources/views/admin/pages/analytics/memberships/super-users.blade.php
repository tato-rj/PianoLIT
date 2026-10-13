<section class="ua-card ma-super-users" id="super-users" aria-labelledby="ma-super-users-title">
    <div class="ma-super-summary">
        <div class="ma-super-total"><span>Super users</span><strong>{{number_format($superUserTotal)}}</strong></div>
        <div class="ma-super-copy"><h2 id="ma-super-users-title">Full access without payment</h2><p>These accounts have full access regardless of subscription status. Their billing records, if any, are shown separately below.</p><small>{{number_format($superUserDated)}} {{str_plural('grant date', $superUserDated)}} recorded · {{number_format($superUserTotal - $superUserDated)}} dates not recorded</small></div>
        @icon('user-shield', ['mr' => 0, 'classes' => 'ma-super-icon'])
    </div>
    <form class="ua-filters" method="GET" action="{{route('admin.analytics.memberships')}}#super-users" aria-label="Super-user filters">
        @foreach($filters as $key => $value)@if($value !== null && $value !== '')<input type="hidden" name="{{$key}}" value="{{$value}}">@endif @endforeach
        <label class="ua-search">Find a super user<input type="search" name="super_search" value="{{$superUserFilters['super_search'] ?? ''}}" placeholder="Name, email or user ID" maxlength="160"></label>
        <label>Sort by<select name="super_sort">@foreach(['name' => 'Account name', 'granted' => 'Latest grant first', 'registered' => 'Latest account first'] as $key => $label)<option value="{{$key}}" @if(($superUserFilters['super_sort'] ?? 'name') === $key) selected @endif>{{$label}}</option>@endforeach</select></label>
        <button class="ua-button" type="submit">Find super users</button>
        @if(array_filter($superUserFilters))<a class="ma-reset" href="{{route('admin.analytics.memberships', array_filter($filters))}}#super-users">Reset</a>@endif
    </form>
    <p class="ma-source-note">{{number_format($superUsers->total())}} matching accounts · Dates shown in {{config('app.timezone')}}.</p>
    <p class="ma-scroll-hint">Scroll sideways to see grant dates and account details.</p>
    <div class="ua-table-scroll" tabindex="0" role="region" aria-label="Super-user accounts"><table class="ua-table ma-super-table">
        <thead><tr><th scope="col">Account</th><th scope="col">Full access since</th><th scope="col">Granted by</th><th scope="col">Account created</th><th scope="col">Signup platform</th></tr></thead>
        <tbody>@forelse($superUsers as $account)
            <tr>
                <td><a class="ua-person-name" href="{{route('admin.users.show', $account->id)}}">{{trim($account->first_name.' '.$account->last_name)}}</a><small>{{$account->email}}</small><small>#{{$account->id}}</small></td>
                <td class="ma-date">@if($account->granted_at){{carbon($account->granted_at)->format('M j, Y')}}<small>{{carbon($account->granted_at)->format('g:i A')}}</small><small>{{carbon($account->granted_at)->diffForHumans()}}</small>@else<span>Not recorded</span>@if($account->first_observed_at)<small>Already enabled on {{carbon($account->first_observed_at)->format('M j, Y')}}</small>@endif @endif</td>
                <td>{{$account->granted_by_name ?: 'Not recorded'}}</td>
                <td class="ma-date">{{$account->registered_at ? carbon($account->registered_at)->format('M j, Y') : 'Not recorded'}}</td>
                <td>{{['web' => 'Website', 'webapp' => 'Webapp', 'ios' => 'iOS', 'android' => 'Android'][$account->origin] ?? ($account->origin ?: 'Not recorded')}}</td>
            </tr>
        @empty<tr><td colspan="5"><div class="ua-empty"><h3>{{$superUserTotal ? 'No matching super users' : 'No super users'}}</h3><p>{{$superUserTotal ? 'Try another name, email or user ID.' : 'Accounts with full access granted without payment will appear here.'}}</p></div></td></tr>@endforelse</tbody>
    </table></div>
    {{$superUsers->fragment('super-users')->links()}}
    <p class="ma-source-note">Older grant dates were not recorded. Account creation dates are shown separately.
        @if($superUserTrackingReady)
        New grants through the admin super-user toggle now record the date and admin responsible.
        @else
        Grant-date tracking needs server setup.
        @endif
    </p>
</section>
